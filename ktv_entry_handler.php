<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/user_input_handler_registry.php';

class KtvEntryHandler
    implements UserInputHandler
{
    private $session;

    public function __construct($session)
    {
        $this->session = $session;

        UserInputHandlerRegistry::get_instance()->
            register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_handler_id()
    {
        return 'entry';
    }

    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_print('Entry handler: handle_user_input:');
        foreach ($user_input as $key => $value)
            hd_print("  $key => $value");

        if (!isset($user_input->entry_id))
            return null;

        $add_params = array(
            'entry_id' => $user_input->entry_id);

        if ($user_input->entry_id === 'setup' ||
            $user_input->entry_id === 'tv')
        {
            $res = $this->session->apply_subscription(
                $plugin_cookies, $user_input);
            if ($res !== false)
            {
                if (!isset($res['action']))
                {
                    return ActionFactory::close_dialog_and_run(
                        ActionFactory::open_folder());
                }

                return $res['need_close_dialog'] ?
                    ActionFactory::close_dialog_and_run($res['action']) :
                    $res['action'];
            }
            else
            {
                if ($this->session->is_logged_in())
                    return ActionFactory::open_folder();

                if (!isset($plugin_cookies->user_name) ||
                    $plugin_cookies->user_name === '')
                {
                    return $this->session->do_get_edit_subscription_action(
                        $plugin_cookies, $this, $add_params);
                }

                return ActionFactory::open_folder();
            }
        }

        return null;
    }
}

///////////////////////////////////////////////////////////////////////////
?>
