<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/hashed_array.php';
require_once 'lib/tv/abstract_tv.php';
require_once 'lib/tv/default_epg_item.php';
require_once 'lib/tv/epg_iterator.php';

require_once 'ktv_channel.php';

///////////////////////////////////////////////////////////////////////////

class KtvTv extends AbstractTv
{
    private $session;

    ///////////////////////////////////////////////////////////////////////

    public function __construct($session)
    {
        $this->session = $session;

        parent::__construct(
            AbstractTv::MODE_CHANNELS_1_TO_N,
            true,
            false);
    }

    public function get_fav_icon_url()
    {
        return $this->session->get_icon('favorites.png');
    }

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////

    protected function load_channels(&$plugin_cookies)
    {
        $this->session->check_logged_in();
		$buf_time = isset($plugin_cookies->buf_time) ? $plugin_cookies->buf_time : 0;
        $settings = $this->session->get_settings();
        $buffering_ms = $settings->http_caching->value;
        $default_timeshift_hours = $settings->timeshift->value;
		//hd_print("buffering_ms --->>> $buffering_ms");
        $this->channels = new HashedArray();
        $this->groups = new HashedArray();

        $this->groups->put(
            new FavoritesGroup(
                $this,
                '__favorites',
                'Избранное',
                $this->session->get_icon('favorites.png')));

        $this->groups->put(
            new AllChannelsGroup(
                $this,
                'Все каналы',
                $this->session->get_icon('all.png')));

        $num_groups = 0;
        $num_channels = 0;
        $num_protected = 0;
        $num_have_archive = 0;
        foreach ($this->session->get_channel_list()->groups as $g)
        {
            $ktv_group = new DefaultGroup(
                $g->id,
                $g->name,
                $this->session->get_group_icon($g->id));
            $this->groups->put($ktv_group);
            $num_groups++;

            foreach ($g->channels as $c)
            {
                $have_archive = isset($c->have_archive) ?
                    ($c->have_archive == 1) : false;
                $is_protected = isset($c->protected) ?
                    ($c->protected == 1) : false;
                
                // TODO: timeshift
                $timeshift_hours = 0;

                $ktv_channel = new KtvChannel(
                    $c->id,
                    $c->name,
                    $this->session->get_channel_icon($c->id),
                    $have_archive, $is_protected,
					$buf_time, $timeshift_hours);
                $this->channels->put($ktv_channel);

                $ktv_channel->add_group($ktv_group);
                $ktv_group->add_channel($ktv_channel);

                $num_channels++;
                if ($is_protected)
                    $num_protected++;
                if ($have_archive)
                    $num_have_archive++;
            }
        }

        hd_print("KTV: $num_groups groups and ".
            "$num_channels channels ($num_have_archive have archive, ".
            "$num_protected protected)");
    }

    public function get_tv_info(MediaURL $media_url, &$plugin_cookies)
    {
        $this->session->ensure_logged_in($plugin_cookies);
        return parent::get_tv_info($media_url, &$plugin_cookies);
    }

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////

    public function get_tv_stream_url($playback_url, &$plugin_cookies)
    {
        return $this->session->api_get_stream_url($playback_url);
    }

    public function get_tv_playback_url($channel_id, $archive_ts, $protect_code, &$plugin_cookies)
    {
		$buf_time = isset($plugin_cookies->buf_time) ? $plugin_cookies->buf_time : 0;
        $this->ensure_channels_loaded($plugin_cookies);

        $url = sprintf(KTV_GET_URL_URL,
            KTV::$SERVER,
            $this->session->get_sid_name(),
            $this->session->get_sid(),
            $channel_id);
		
        if (intval($archive_ts) > 0)
            $url .= "&gmt=$archive_ts";
        if (isset($protect_code) && $protect_code !== '')
            $url .= "&protect_code=$protect_code";
		$url .= "|||dune_params|||buffering_ms:$buf_time";
		hd_print("url: --->>>$url");
        return $url;
    }

    ///////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////

    public function get_day_epg_iterator($channel_id, $day_start_ts, &$plugin_cookies)
    {
        $this->ensure_channels_loaded($plugin_cookies);

        $day_str = gmstrftime('%d%m%y', $day_start_ts);
        $url = sprintf(KTV_EPG_URL,
            KTV::$SERVER,
            $this->session->get_sid_name(),
            $this->session->get_sid(),
            $channel_id,
            $day_str);

        try
        {
            $ktv_epg = $this->session->api_call($url);
        }
        catch (Exception $e)
        {
            throw $this->session->dune_api_exception($e,
                'EPG request failed.',
                true);
        }

        $epg_items = array();

        foreach ($ktv_epg->epg as $epg)
        {
            $progname = $epg->progname;

            $arr = explode("\n", $epg->progname, 2);
            $name = count($arr) >= 1 ? trim($arr[0]) : '';
            $description = count($arr) == 2 ? $arr[1] : '';

            $start = intval($epg->ut_start);
            $end = -1;

            $epg_items[] = new DefaultEpgItem(
                $name, $description, $start, $end);
        }

        return new EpgIterator($epg_items, $day_start_ts, $day_start_ts + 86400);
    }

    public function get_archive(MediaURL $media_url)
    {
        return $this->session->get_archive();
    }

    public function folder_entered(MediaURL $media_url, &$plugin_cookies)
    {
        if (!isset($media_url->screen_id) ||
            $media_url->screen_id === TvGroupListScreen::ID)
        {
            $this->session->logout();
        }

        $this->session->ensure_logged_in($plugin_cookies);
    }

    // Hook for adding special group items.
    public function add_special_groups(&$items)
    {
        array_unshift($items,
            array
            (
                PluginRegularFolderItem::media_url =>
                    MediaURL::encode(
                        array
                        (
                            'screen_id' => KtvVodRootScreen::ID,
                        )),
                PluginRegularFolderItem::caption => 'Видеотека',
                PluginRegularFolderItem::view_item_params => array
                (
                    ViewItemParams::icon_path =>
                        $this->session->get_icon('mov_root.png')
                )
            ));
    }
}

///////////////////////////////////////////////////////////////////////////
?>
