<?php
///////////////////////////////////////////////////////////////////////////

const ECODE_QUERY_LIMIT_EXCEEDED = 31;

class KTV
{
    public static $SERVER = 'iptv-kartina.tv';
}

const KTV_LOGIN_URL =
    'http://%s/api/json/login?login=%s&pass=%s&settings=all';

const KTV_CHANNEL_LIST_URL =
    'http://%s/api/json/channel_list?%s=%s';

const KTV_SET_SETTING_URL =
    'http://%s/api/json/settings_set?%s=%s&var=%s&val=%s';

const KTV_CHANGE_PCODE_URL =
    'http://%s/api/json/settings_set?%s=%s&var=pcode&old_code=%s&new_code=%s&confirm_code=%s';

const KTV_EPG_URL =
    'http://%s/api/json/epg?%s=%s&cid=%s&day=%s';

const KTV_GET_URL_URL =
    'http://ts://%s/api/json/get_url?%s=%s&cid=%s';

const KTV_VOD_LIST_URL_PREFIX =
    'http://%s/api/json/vod_list?%s=%s&type=%s&page=%s&nums=%s';

const KTV_VOD_INFO_URL =
    'http://%s/api/json/vod_info?%s=%s&id=%s';

const KTV_VOD_FAVLIST_URL =
    'http://%s/api/json/vod_favlist?%s=%s';

const KTV_VOD_GENRES_URL =
    'http://%s/api/json/vod_genres?%s=%s';

const KTV_VOD_FAVADD_URL=
    'http://%s/api/json/vod_favadd?%s=%s&id=%s';

const KTV_VOD_FAVSUB_URL=
    'http://%s/api/json/vod_favsub?%s=%s&id=%s';

const KTV_VOD_GET_URL_URL =
    'http://mp4://%s/api/json/vod_geturl?%s=%s&fileid=%s';

const KTV_ARCHIVE_URL_PREFIX = 'http://lubiteli.ru/ktv2';

const KTV_ARCHIVE_ID = 'main';

class KtvSession
{
    private $http_opts = null;

    private $logged_in = false;
    private $sid_name = null;
    private $sid = null;

    private $login_incorrect = false;

    private $account = null;
    private $services = null;
    private $settings = null;

    private $channel_list = null;

    private $unset_login_listener = null;

    ///////////////////////////////////////////////////////////////////////

    public function __construct($unset_login_listener)
    {
        $this->http_opts = array (
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 10,
// debug            CURLOPT_PROXY           => '192.168.1.9:3128',
            CURLOPT_COOKIEFILE      => '/tmp/ktv.cookies',
            CURLOPT_COOKIEJAR       => '/tmp/ktv.cookies'
        );

        $this->unset_login_listener = $unset_login_listener;
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_channel_list()
    { return $this->channel_list; }

    public function get_account()
    { return $this->account; }

    public function get_settings()
    { return $this->settings; }

    ///////////////////////////////////////////////////////////////////////

    public function is_login_incorrect()
    { return $this->login_incorrect; }

    public function is_logged_in()
    { return $this->logged_in; }

    public function unset_login()
    {
        $this->logged_in = false;
        $this->sid_name = null;
        $this->sid = null;

        $this->account = null;
        $this->services = null;

        $this->settings = null;

        $this->channel_list = null;
    }

    public function logout()
    {
        $this->unset_login_listener->unset_login();
    }

    ///////////////////////////////////////////////////////////////////////

    private function fetch_document($url)
    {
        $doc = HD::http_get_document($url, $this->http_opts);

        // assert($doc != null).

        return $doc;
    }

    public function api_call($url, $silent=true)
    {
        for (;;)
        {
            $str = $this->fetch_document($url);

            if (!$silent)
                echo("Document:\n$str\n");

            $reply = json_decode($str);
            if ($reply === null)
                throw new Exception('Invalid data received from server');

            if (isset($reply->error))
            {
                $ecode = intval($reply->error->code);

                if ($ecode === ECODE_QUERY_LIMIT_EXCEEDED)
                {
                    hd_print('API: query limit exceeded');
                    continue;
                }

                hd_print("API error: URL '$url' " .
                    "returned error with code=$ecode, msg=" .
                    $reply->error->message . '.');

                throw new KtvException(
                    $reply->error->message, $ecode);
            }

            break;
        }

        return $reply;
    }

    ///////////////////////////////////////////////////////////////////////

    public function dune_api_exception($e, $def_caption, $is_playback)
    {
        $is_ktv_error = ($e instanceof KtvException);
        if (!$is_ktv_error)
        {
            hd_print('General exception: ' . $e->getMessage());
            $title = !$is_playback ? 'Error' :
                'Failed to connect to Internet.';
            $text_lines = $is_playback ? array() :
                array(
                 'Failed to connect to Internet.',
                 'Please check Internet connection.'
                );
            return new DuneException(
                $def_caption, -1,
                ActionFactory::show_error(false, $title, $text_lines));
        }
        hd_print('Kartina.TV API exception: ' .
            'code=' . $e->getCode() . ', message=' . $e->getMessage());

        $ecode = $e->getCode();
        $fatal = $ecode == 5 || $ecode == 6 ||
            $ecode == 11 || $ecode == 12 || $ecode == 13;

        if ($fatal)
            $this->logout();

        if ($ecode == 2 || $ecode == 4)
        {
            $this->login_incorrect = true;

            $title = !$is_playback ? 'Error' :
                'Subscription is invalid, please check settings.';
            $text_lines = $is_playback ? array() :
                array('Subscription is invalid, please check settings.');
            return new DuneException(
                $def_caption, $ecode,
                ActionFactory::show_error($fatal, $title, $text_lines));
        }

        if ($ecode == 3)
        {
            $title = !$is_playback ? 'Error' :
                'Access denied for 10 minutes.';
            $text_lines = $is_playback ? array() :
                array('Access denied for 10 minutes.');
            return new DuneException(
                $def_caption, $ecode,
                ActionFactory::show_error($fatal, $title, $text_lines));
        }

        if ($ecode == 12)
        {
            $title = !$is_playback ? 'Error' :
                'Access denied. Probably subscription is used elsewhere.';
            $text_lines = $is_playback ? array() :
                array('Access denied.',
                    'Probably subscription is used elsewhere.');
            return new DuneException(
                $def_caption, $ecode,
                ActionFactory::show_error($fatal, $title, $text_lines));
        }

        if ($ecode == 11)
        {
            $title = !$is_playback ? 'Error' :
                'Subscription is used elsewhere.';
            $text_lines = $is_playback ? array() :
                array('Subscription is used elsewhere.');
            return new DuneException(
                $def_caption, $ecode,
                ActionFactory::show_error($fatal, $title, $text_lines));
        }

        if ($ecode == 19)
        {
            $title = !$is_playback ? 'Error' :
                'Current code is wrong.';
            $text_lines = $is_playback ? array() :
                array('Current code is wrong.');
            return new DuneException(
                $def_caption, $ecode,
                ActionFactory::show_error($fatal, $title, $text_lines));
        }

        $title = !$is_playback ? 'Error' : $e->getMessage();
        $text_lines = $is_playback ? array() :
            array('Description: ' . $e->getMessage(),
                'Error code: ' . $ecode);
        return new DuneException(
            $def_caption, $ecode,
            ActionFactory::show_error($fatal, $title, $text_lines));
    }

    ///////////////////////////////////////////////////////////////////////

    public function api_vod_favadd($movie_id)
    {
        $this->check_logged_in();

        try
        {
            $this->api_call(
                sprintf(
                    KTV_VOD_FAVADD_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid,
                    $movie_id));
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'VOD favadd failed.', false);
        }
    }

    public function api_vod_favsub($movie_id)
    {
        $this->check_logged_in();

        try
        {
            $this->api_call(
                sprintf(
                    KTV_VOD_FAVSUB_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid,
                    $movie_id));
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'VOD favadd failed.', false);
        }
    }

    public function api_vod_info($movie_id)
    {
        $this->check_logged_in();

        try
        {
            $doc = $this->api_call(
                sprintf(
                    KTV_VOD_INFO_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid,
                    $movie_id));
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'VOD info failed.', false);
        }

        if (!isset($doc->film))
            throw new Exception('Invalid data received from server');

        $movie = new Movie($movie_id);

        $poster_url = 'http://' . KTV::$SERVER . $doc->film->poster;

        $movie->set_data(
            $doc->film->name,
            $doc->film->name_orig,
            $doc->film->description,
            $poster_url,
            $doc->film->lenght,
            $doc->film->year,
            $doc->film->director,
            $doc->film->scenario,
            $doc->film->actors,
            $doc->film->genre_str,
            $doc->film->rate_imdb,
            $doc->film->rate_kinopoisk,
            $doc->film->rate_mpaa,
            $doc->film->country,
            $doc->film->budget);

        foreach ($doc->film->videos as $file)
        {
            $playback_url =
                sprintf(KTV_VOD_GET_URL_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid,
                    $file->id);

            $movie->add_series_data(
                $file->id,
                $file->title,
                $playback_url,
                false);
        }

        return $movie;
    }

    public function api_get_stream_url($playback_url)
    {
        $this->check_logged_in();

        if (substr($playback_url, 0, 12) === 'http://ts://')
            $playback_url = 'http://' . substr($playback_url, 12);
        else if (substr($playback_url, 0, 13) === 'http://mp4://')
            $playback_url = 'http://' . substr($playback_url, 13);

        try
        {
            $doc = $this->api_call($playback_url);
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'Stream url request failed.',
                true);
        }

        if (!isset($doc->url))
            throw new Exception('Get_url failed.');

        $len = strlen($doc->url);
        $pos = $len;
        for ($i = 10; $i < $len; $i++)
        {
            if ($doc->url[$i] == '"' || $doc->url[$i] == ' ')
            {
                $pos = $i;
                break;
            }
        }

        $url = substr($doc->url, 0, $pos);

        if (substr($url, 0, 10) === 'http/ts://')
            return 'http://' . substr($url, 10);

        return $url;
    }

    public function api_set_setting($name, $value)
    {
        $this->check_logged_in();

        try
        {
            $doc = $this->api_call(
                sprintf(
                    KTV_SET_SETTING_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid,
                    $name, $value),
                false);
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e, 'Set setting failed.', false);
        }

        $this->settings->{$name}->value = $value;
    }

    public function api_change_pcode($old_code, $new_code, $confirm_code)
    {
        $this->check_logged_in();

        try
        {
            $doc = $this->api_call(
                sprintf(
                    KTV_CHANGE_PCODE_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid,
                    $old_code, $new_code, $confirm_code),
                false);
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'Change protect code failed.', false);
        }
    }

    private function api_vod_list($type, $query, $genre, $page, $nums)
    {
        $this->check_logged_in();

        $url = sprintf(KTV_VOD_LIST_URL_PREFIX,
            KTV::$SERVER, $this->sid_name, $this->sid,
            $type, $page, $nums);
        if ($genre !== null)
            $url .= "&genre=$genre";
        if ($query !== null)
            $url .= "&query=$query";

        try
        {
            $doc = $this->api_call($url);
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'VOD list request failed.', false);
        }

        return $doc;
    }

    public function api_vod_list_last($page, $nums)
    {
        return $this->api_vod_list('last', null, null, $page, $nums);
    }

    public function api_vod_list_best($page, $nums)
    {
        return $this->api_vod_list('best', null, null, $page, $nums);
    }

    public function api_vod_list_search($query, $page, $nums)
    {
        return $this->api_vod_list('text', $query, null, $page, $nums);
    }

    public function api_vod_list_genres($genre, $page, $nums)
    {
        return $this->api_vod_list('last', null, $genre, $page, $nums);
    }

    public function api_vod_favlist()
    {
        $this->check_logged_in();

        try
        {
            $doc = $this->api_call(
                sprintf(KTV_VOD_FAVLIST_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid));
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'VOD favlist request failed.', false);
        }

        return $doc;
    }

    public function api_vod_genres()
    {
        $this->check_logged_in();

        try
        {
            $doc = $this->api_call(
                sprintf(KTV_VOD_GENRES_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid));
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'VOD genres request failed.', false);
        }

        return $doc;
    }

    private function api_login($user_name, $password)
    {
        try
        {
            $doc = $this->api_call(
                sprintf(
                    KTV_LOGIN_URL, KTV::$SERVER,
                    $user_name, $password),
                false);
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e, 'Login failed.', false);
        }

        $this->logged_in = true;
        $this->sid = $doc->sid;
        $this->sid_name = $doc->sid_name;
        $this->login_incorrect = false;
        $this->account = $doc->account;
        $this->services = $doc->services;
        $this->settings = $doc->settings;
    }

    private function api_channel_list()
    {
        try
        {
            $doc = $this->api_call(
                sprintf(
                    KTV_CHANNEL_LIST_URL, KTV::$SERVER,
                    $this->sid_name, $this->sid));
        }
        catch (Exception $e)
        {
            throw $this->dune_api_exception($e,
                'Channel list request failed.', false);
        }

        $this->channel_list = $doc;
    }

    ///////////////////////////////////////////////////////////////////////

    public function try_login(&$plugin_cookies)
    {
        if (!isset($plugin_cookies->user_name))
            throw new Exception('User name is not set');

        try
        {
            $this->api_login(
                $plugin_cookies->user_name,
                $plugin_cookies->password);

            $this->api_channel_list();
        }
        catch (Exception $e)
        {
            $this->logout();
            throw $e;
        }
    }

    public function ensure_logged_in(&$plugin_cookies)
    {
        if ($this->logged_in)
            return;

        $this->logout();

        $this->try_login($plugin_cookies);
    }

    public function check_logged_in()
    {
        if (!$this->logged_in)
            throw new KtvException('Not logged in', 12);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_sid()
    {
        if (!$this->logged_in)
            throw new KtvException('Not logged in', 12);
        return $this->sid;
    }

    public function get_sid_name()
    {
        if (!$this->logged_in)
            throw new KtvException('Not logged in', 12);
        return $this->sid_name;
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_icon($id)
    {
        $archive = DefaultArchive::get_archive(
            KTV_ARCHIVE_ID,
            KTV_ARCHIVE_URL_PREFIX);

        return $archive->get_archive_url($id);
    }

    public function get_group_icon($group_id)
    {
        return $this->get_icon("group_$group_id.png");
    }

    public function get_channel_icon($channel_id)
    {
        return $this->get_icon("channel_$channel_id.png");
    }

    ///////////////////////////////////////////////////////////////////////

    private function do_get_edit_subscription_defs(
        &$plugin_cookies, $handler, $add_params)
    {
        $defs = array();

        $user_name = isset($plugin_cookies->user_name) ?
            $plugin_cookies->user_name : '';
        $password = isset($plugin_cookies->password) ?
            $plugin_cookies->password : '';

        ControlFactory::add_text_field($defs,
            $handler, $add_params,
            'user_name', 'Абонемент:',
            $user_name, true, false, false, 1, 600, 0);

        ControlFactory::add_text_field($defs,
            $handler, $add_params,
            'password', 'Пароль:',
            $password, true, true, false, 1, 600, 0);

        ControlFactory::add_vgap($defs, 100);

        ControlFactory::add_button($defs,
            $handler, $add_params,
            'apply_subscription', null, 'Применить', 300);

        ControlFactory::add_vgap($defs, -3);

        ControlFactory::add_close_dialog_button($defs,
            'Отмена', 300);

        return $defs;
    }

    public function do_get_edit_subscription_action(
        &$plugin_cookies, $handler, $add_params = null)
    {
        return ActionFactory::show_dialog(
            'Введите данные або Kartina.TV',
            $this->do_get_edit_subscription_defs(
                $plugin_cookies, $handler, $add_params),
            true);
    }

    public function apply_subscription(&$plugin_cookies, &$user_input)
    {
        if (!isset($user_input->control_id) ||
            $user_input->control_id != 'apply_subscription')
        {
            return false;
        }

        if ($user_input->user_name === '')
        {
            return array(
                'need_close_dialog' => false,
                'action' =>
                    ActionFactory::show_error(false,
                        'Ошибка',
                        array('Subscription should be non-empty.')));
        }

        $plugin_cookies->user_name = $user_input->user_name;
        $plugin_cookies->password = $user_input->password;

        $this->logout();

        $post_action = null;
        try
        {
            $this->try_login($plugin_cookies);
        }
        catch (DuneException $e)
        {
            $post_action = $e->get_error_action();
        }

        return array(
            'need_close_dialog' => true,
            'action' => $post_action);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_archive()
    {
        return DefaultArchive::get_archive(
            KTV_ARCHIVE_ID, KTV_ARCHIVE_URL_PREFIX);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
