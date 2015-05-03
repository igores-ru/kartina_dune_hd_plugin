<?php
///////////////////////////////////////////////////////////////////////////

class KtvTvGroupListScreen extends TvGroupListScreen
{
    private $session;

    public function __construct($session, $tv, $folder_views)
    {
        parent::__construct($tv, $folder_views);

        $this->session = $session;
    }

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->session->logout();
        $this->session->ensure_logged_in($plugin_cookies);
        $this->tv->ensure_channels_loaded($plugin_cookies);

        $new_items[] = array
        (
            PluginRegularFolderItem::media_url =>
                MediaURL::encode(
                    array
                    (
                        'screen_id' => KtvVodRootScreen::ID,
                    )),
            PluginRegularFolderItem::caption => 'Videoteka',
            PluginRegularFolderItem::view_item_params => array
            (
                ViewItemParams::icon_path =>
                    $this->session->get_icon('mov_root.png')
            )
        );

        $parent_items = 
            parent::get_all_folder_items($media_url, $plugin_cookies);

        return array_merge($new_items, $parent_items);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
