<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/default_dune_plugin.php';
require_once 'lib/default_archive.php';
require_once 'lib/tv/tv_group_list_screen.php';
require_once 'lib/tv/tv_channel_list_screen.php';
require_once 'lib/tv/tv_favorites_screen.php';
require_once 'lib/vod/vod_list_screen.php';
require_once 'lib/vod/vod_search_screen.php';
require_once 'lib/vod/vod_genres_screen.php';
require_once 'lib/vod/vod_movie_screen.php';
require_once 'lib/vod/vod_series_list_screen.php';
require_once 'lib/vod/vod_favorites_screen.php';

require_once 'ktv_exception.php';
require_once 'ktv_tv.php';
require_once 'ktv_vod.php';
require_once 'ktv_session.php';
require_once 'ktv_setup_screen.php';
require_once 'ktv_vod_root_screen.php';
require_once 'ktv_vod_list_screen.php';
require_once 'ktv_entry_handler.php';

///////////////////////////////////////////////////////////////////////////

class KtvPlugin extends DefaultDunePlugin
{
    private $session;
    private $entry_handler;

    public function __construct()
    {
        $this->session = new KtvSession($this);
        $this->entry_handler = new KtvEntryHandler($this->session);
        $this->tv = new KtvTv($this->session);
        $this->vod = new KtvVod($this->session);

        $tv_folder_views = $this->get_tv_folder_views();

        $this->add_screen(new KtvSetupScreen($this->session));

        $this->add_screen(
            new TvGroupListScreen($this->tv, $tv_folder_views));

        $this->add_screen(
            new TvChannelListScreen($this->tv, $tv_folder_views));

        $this->add_screen(
            new TvFavoritesScreen($this->tv, $tv_folder_views));

        $this->add_screen(
            new KtvVodRootScreen($this->session, $tv_folder_views));

        $this->add_screen(
            new KtvVodListScreen($this->session, $this->vod));

        $this->add_screen(new VodMovieScreen($this->vod));

        $this->add_screen(new VodSeriesListScreen($this->vod));

        $this->add_screen(new VodFavoritesScreen($this->vod));

        $this->add_screen(new VodSearchScreen($this->vod));

        $this->add_screen(new VodGenresScreen($this->vod));
    }

    public function unset_login()
    {
        $this->session->unset_login();
        $this->tv->unload_channels();
        $this->vod->clear_movie_cache();
        $this->vod->clear_genre_cache();

        DefaultArchive::clear_cache();
    }

    private function get_tv_folder_views()
    {
        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 5,
                    ViewParams::num_rows => 4,
                    ViewParams::icon_selection_box_width => 294,
                    ViewParams::icon_selection_box_height => 162,
                    ViewParams::icon_selection_box_dy => -78,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.0,
                    ViewItemParams::icon_sel_scale_factor => 1.2,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/template.png',
                    ViewItemParams::item_paint_caption_within_icon => true,
                    ViewItemParams::item_caption_within_icon_color => 'black',
                ),
            ),

            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 4,
                    ViewParams::num_rows => 3,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_paint_caption => false,
                    ViewItemParams::icon_scale_factor => 1.25,
                    ViewItemParams::icon_sel_scale_factor => 1.5,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => 'plugin_file://icons/template.png',
                    ViewItemParams::item_paint_caption_within_icon => true,
                    ViewItemParams::item_caption_within_icon_color => 'black',
                ),
            ),
        );
    }
}

///////////////////////////////////////////////////////////////////////////
?>
