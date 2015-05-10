<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/abstract_preloaded_regular_screen.php';

class KtvVodRootScreen extends AbstractPreloadedRegularScreen
{
    const ID = 'vod_root';

    private $session;

    public function __construct($session, $folder_views)
    {
        parent::__construct(self::ID, $folder_views);

        $this->session = $session;
    }

    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        return array
        (
            GUI_EVENT_KEY_ENTER => ActionFactory::open_folder(),
        );
    }

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $defs = array(
            array(
                KtvVodListScreen::get_media_url_str('last'),
                'Последнее', 'mov_last.png'),
            array(
                KtvVodListScreen::get_media_url_str('best'),
                'Лучшие', 'mov_best.png'),
            array(
                VodFavoritesScreen::get_media_url_str(),
                'Мои фильмы', 'mov_favorites.png'),
            array(
                VodGenresScreen::get_media_url_str(),
                'Жанры', 'mov_genres.png'),
            array(
                VodSearchScreen::get_media_url_str(),
                'Поиск', 'mov_search.png')
        );

        $items = array();

        foreach ($defs as $def)
        {
            $items[] = array
            (
                PluginRegularFolderItem::media_url => $def[0],
                PluginRegularFolderItem::caption => $def[1],
                PluginRegularFolderItem::view_item_params => array
                (
                    ViewItemParams::icon_path =>
                        $this->session->get_icon($def[2])
                )
            );
        }

        return $items;
    }
}

///////////////////////////////////////////////////////////////////////////
?>
