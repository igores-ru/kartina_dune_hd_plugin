<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_controls_screen.php';

///////////////////////////////////////////////////////////////////////////

class KtvSetupScreen extends AbstractControlsScreen
{
    const ID = 'setup';

    ///////////////////////////////////////////////////////////////////////

    private $session;
	
    ///////////////////////////////////////////////////////////////////////

    public function __construct($session)
    {
        parent::__construct(self::ID);

        $this->session = $session;
    }

    private function get_http_caching_caption($value)
    {
        if ($value % 1000 == 0)
            return sprintf('%d сек', intval($value / 1000));
         return sprintf('%.1f сек', $value / 1000.0);
    }

    private function do_get_edit_pcode_defs()
    {
        $defs = array();

        $this->add_text_field($defs,
            'current_pcode', 'Текущий код:',
            '', true, true, false, 1, 500, 0);

        $this->add_text_field($defs,
            'new_pcode', 'Новый код:',
            '', true, true, false, 1, 500, 0);

        $this->add_text_field($defs,
            'new_pcode_copy', 'Подтвердить:',
            '', true, true, false, 1, 500, 0);

        $this->add_vgap($defs, 50);

        $this->add_button($defs,
            'apply_pcode', null, 'Применить', 300);

        $this->add_vgap($defs, -3);

        $this->add_close_dialog_button($defs,
            'Отмена', 300);

        return $defs;
    }

    private function do_get_edit_pcode_action()
    {
        return ActionFactory::show_dialog(
            'Изменение кода закрытых каналов',
            $this->do_get_edit_pcode_defs(),
            true);
    }

    private function do_get_control_defs(&$plugin_cookies)
    {
        $defs = array();

        $user_name = isset($plugin_cookies->user_name) ?
            $plugin_cookies->user_name : '';

        $logged_in = $this->session->is_logged_in();

        hd_print('Packet name: ' .
            ($logged_in ? $this->session->get_account()->packet_name : 'unset'));
        if ($user_name === '')
            $login_str = 'unset';
        else if (!$logged_in ||
            !isset($this->session->get_account()->packet_name))
        {
            $login_str = $user_name;
        }
        else
        {
            $login_str = $user_name . ' (' .
                $this->session->get_account()->packet_name . ')';
        }

        hd_print('Packet expire: ' .
            ($logged_in ? $this->session->get_account()->packet_expire : 'unset'));
        if (!$logged_in ||
            !isset($this->session->get_account()->packet_expire) ||
            $this->session->get_account()->packet_expire <= 0)
        {
            $expires_str = 'not available';
        }
        else
        {
            $tm = $this->session->get_account()->packet_expire;

            $expires_str =
                HD::format_date_time_date($tm) . ', ' .
                HD::format_date_time_time($tm);
        }

        $this->add_label($defs,
            'Абонемент:', $login_str);

        $this->add_label($defs,
            'Истекает:', $expires_str);

        $this->add_button($defs, 'edit_subscription', null,
            'Редактировать абонемент...', 700);

        $settings = $this->session->get_settings();

        $stream_server_caption = 'not available';
        $bitrate_caption = 'not available';
        $http_caching_caption = 'not available';
        $timeshift_caption = 'not available';

        if (isset($settings))
        {
            $stream_server = $settings->stream_server->value;
            foreach ($settings->stream_server->list as $pair)
            {
                if ($pair->ip === $stream_server)
                {
                    $stream_server_caption = $pair->descr;
                    break;
                }
            }
            
            $bitrate= $settings->bitrate->value;
            $bitrate_caption = $bitrate;
			
            $http_caching = $settings->http_caching->value;
			$http_caching_caption = $http_caching;

            $timeshift = $settings->timeshift->value;
            $timeshift_caption = $timeshift;
        }

        if ($logged_in)
        {
            $this->add_button($defs, 'edit_pcode', 'Код для закрытых каналов:',
                'Изменить...', 700);

            $stream_server_ops = array();
            foreach ($settings->stream_server->list as $pair)
                $stream_server_ops[$pair->ip] = $pair->descr;
            $this->add_combobox($defs,
                'stream_server', 'Сервер вещания:',
                $stream_server, $stream_server_ops, 700, true);

            $bitrate_ops = array();
            foreach ($settings->bitrate->list as $v)
                $bitrate_ops[$v] = $v;
            $this->add_combobox($defs,
                'bitrate', 'Битрейт:',
                $bitrate, $bitrate_ops, 700, true);

            $http_caching_ops = array();
//			array_unshift($settings->http_caching->list, "500");
//			echo "<pre>"; print_r($settings->http_caching->list);
			foreach ($settings->http_caching->list as $v)
                $http_caching_ops[$v] = $this->get_http_caching_caption($v);
            $this->add_combobox($defs,
                'http_caching', 'Время буфферизации:',
                $http_caching, $http_caching_ops, 700, true);

			

            $timeshift_ops = array();
            foreach ($settings->timeshift->list as $v)
                $timeshift_ops[$v] = $v;
            $this->add_combobox($defs,
                'timeshift', 'Задержка по времени (часы):',
                $timeshift, $timeshift_ops, 700, true);
        }
        else
        {
            $this->add_label($defs,
                'Код для закрытых каналов:', 'not available');

            $this->add_label($defs,
                'Сервер вещания:', $stream_server_caption);
            $this->add_label($defs,
                'Битрейт:', $bitrate_caption);
            $this->add_label($defs,
                'Время буфферизации:', $http_caching_caption);
            $this->add_label($defs,
                'Задержка по времени (часы):', $timeshift_caption);
        }

        if (isset($plugin_cookies->show_in_main_screen))
            $show_in_main_screen = $plugin_cookies->show_in_main_screen;
        else
            $show_in_main_screen = 'auto';

        $show_ops = array();
        $show_ops['auto'] = 'Автоматически';
        $show_ops['yes'] = 'Да';
        $show_ops['no'] = 'Нет';
        $this->add_combobox($defs,
            'show_in_main_screen', 'Показывать в главном меню:',
            $show_in_main_screen, $show_ops, 0, true);

        return $defs;
    }

    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        try
        {
            if (!$this->session->is_login_incorrect())
                $this->session->ensure_logged_in($plugin_cookies);
        }
        catch (Exception $e)
        {
            // Nop.
        }

        return $this->do_get_control_defs($plugin_cookies);
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_print('Setup: handle_user_input:');
        foreach ($user_input as $key => $value)
            hd_print("  $key => $value");

        $need_close_dialog = false;
        $need_reset_controls = false;
        $post_action = null;
        if ($user_input->action_type === 'apply')
        {
            $control_id = $user_input->control_id;
            if ($control_id === 'edit_subscription')
            {
                return $this->session->do_get_edit_subscription_action(
                    $plugin_cookies, $this);
            }
            else if ($control_id === 'apply_subscription')
            {
                if ($user_input->user_name === '')
                {
                    return ActionFactory::show_error(false,
                        'Error',
                        array('Subscription should be non-empty.'));
                }

                $plugin_cookies->user_name = $user_input->user_name;
                $plugin_cookies->password = $user_input->password;

                $this->session->logout();

                try
                {
                    $this->session->try_login($plugin_cookies);
                }
                catch (DuneException $e)
                {
                    $post_action = $e->get_error_action();
                }

                $need_close_dialog = true;
                $need_reset_controls = true;
            }
            else if ($control_id === 'edit_pcode')
            {
                return $this->do_get_edit_pcode_action();
            }
            else if ($control_id === 'apply_pcode')
            {
                try
                {
                    $this->session->api_change_pcode(
                        $user_input->current_pcode,
                        $user_input->new_pcode,
                        $user_input->new_pcode_copy);
                }
                catch (DuneException $e)
                {
                    return $e->get_error_action();
                }

                $need_close_dialog = true;
                $need_reset_controls = true;
            }
        }
        else if ($user_input->action_type === 'confirm')
        {
            $control_id = $user_input->control_id;
            $new_value = $user_input->{$control_id};
            hd_print("Setup: changing $control_id value to $new_value");

            if ($control_id === 'show_in_main_screen')
            {
                $plugin_cookies->show_in_main_screen = $new_value;
                if ($new_value === 'auto')
                    $plugin_cookies->show_tv = 'lang(russian)';
                else
                    $plugin_cookies->show_tv = $new_value;
            }
            else if ($control_id === 'bitrate' ||
                $control_id == 'stream_server' ||
                $control_id == 'http_caching' ||
                $control_id == 'timeshift')
            {
                try
                {
                    $this->session->api_set_setting($control_id, $new_value);
                }
                catch (DuneException $e)
                {
                    return $e->get_error_action();
                }
            }
            else
                return null;

            $need_reset_controls = true;
        }

        if ($need_reset_controls)
        {
            $defs = $this->do_get_control_defs($plugin_cookies);

            $reset_controls_action = ActionFactory::reset_controls(
                $defs, $post_action);

            if ($need_close_dialog)
            {
                return ActionFactory::close_dialog_and_run(
                    $reset_controls_action);
            }

            return $reset_controls_action;
        }

        return null;
    }
}

///////////////////////////////////////////////////////////////////////////
?>
