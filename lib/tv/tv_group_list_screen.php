<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_preloaded_regular_screen.php';
require_once 'lib/abstract_controls_screen.php';
///////////////////////////////////////////////////////////////////////////

class TvGroupListScreen extends AbstractPreloadedRegularScreen 
implements UserInputHandler
{
    const ID = 'tv_group_list';

    ///////////////////////////////////////////////////////////////////////

    protected $tv;
	public function get_handler_id()
	{
		return self::ID;
	}
    ///////////////////////////////////////////////////////////////////////

    public function __construct($tv, $folder_views)
    {
        parent::__construct(self::ID, $folder_views);

        $this->tv = $tv;
		
		UserInputHandlerRegistry::get_instance()->register_handler($this);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
		//////////////////////////////////igores////////////////////////////////////////////
		$setup_view = UserInputHandlerRegistry::create_action($this, 'do_setup_menu');
        $setup_view['caption'] = 'Настройки плагина';
		//////////////////////////////////////////////////////////////////////////////
		
        return array
        (
            GUI_EVENT_KEY_ENTER => ActionFactory::open_folder(),
            GUI_EVENT_KEY_PLAY  => ActionFactory::tv_play(),
			GUI_EVENT_KEY_D_BLUE => $setup_view,
        );
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->tv->folder_entered($media_url, $plugin_cookies);

        $this->tv->ensure_channels_loaded($plugin_cookies);

        $items = array();

        foreach ($this->tv->get_groups() as $group)
        {
            $media_url = $group->is_favorite_channels() ?
                TvFavoritesScreen::get_media_url_str() :
                TvChannelListScreen::get_media_url_str($group->get_id());

            $items[] = array
            (
                PluginRegularFolderItem::media_url => $media_url,
                PluginRegularFolderItem::caption => $group->get_title(),
                PluginRegularFolderItem::view_item_params => array
                (
                    ViewItemParams::icon_path => $group->get_icon_url(),
                    ViewItemParams::item_detailed_icon_path => $group->get_icon_url()
                )
            );
        }

        $this->tv->add_special_groups($items);

        return $items;
    }

    public function get_archive(MediaURL $media_url)
    {
        return $this->tv->get_archive($media_url);
    }
	public function handle_user_input(&$user_input, &$plugin_cookies)
	{
        hd_print('TVGroupListScreen: handle_user_input:');
        foreach ($user_input as $key => $value)
            hd_print("  $key => $value");
			
		if($user_input->control_id == 'do_setup_menu')
		{
		return ActionFactory::open_folder('setup');
		}
	}
}

///////////////////////////////////////////////////////////////////////////
?>
