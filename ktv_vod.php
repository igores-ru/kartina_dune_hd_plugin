<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/vod/abstract_vod.php';
require_once 'lib/vod/movie.php';

///////////////////////////////////////////////////////////////////////////

class KtvVod extends AbstractVod
{
    private $session;

    public function __construct($session)
    {
        $this->session = $session;

        parent::__construct(true, true, false);
    }

    ///////////////////////////////////////////////////////////////////////

    public function get_vod_info(MediaURL $media_url, &$plugin_cookies)
    {
        $this->session->ensure_logged_in($plugin_cookies);

        return parent::get_vod_info($media_url, &$plugin_cookies);
    }

    public function try_load_movie($movie_id, &$plugin_cookies)
    {
        $movie = $this->session->api_vod_info($movie_id);

        $this->set_cached_movie($movie);
    }

    public function get_vod_stream_url($playback_url, &$plugin_cookies)
    {
        return $this->session->api_get_stream_url($playback_url);
    }

    public function get_buffering_ms()
    {
        $this->session->check_logged_in();

        $settings = $this->session->get_settings();
        return $settings->http_caching->value;
    }

    ///////////////////////////////////////////////////////////////////////
    // Favorites.

    protected function load_favorites(&$plugin_cookies)
    {
        $doc = $this->session->api_vod_favlist();

        if (!isset($doc->total))
            throw new Exception('Invalid data returned from server');

        $total = intval($doc->total);
        if ($total === 0)
        {
            $this->set_fav_movie_ids(array());
            return;
        }

        $fav_movie_ids = array();
        foreach ($doc->rows as $row)
        {
            $movie_id = $row->id;
            $movie_name = $row->name;
            $poster_url = 'http://' . KTV::$SERVER . $row->poster;

            $fav_movie_ids[] = $movie_id;
            $this->set_cached_short_movie(
                new ShortMovie($movie_id, $movie_name, $poster_url));
        }
        $this->set_fav_movie_ids($fav_movie_ids);
        hd_print('The ' . count($fav_movie_ids) . ' favorite movies loaded.');
    }

    protected function do_add_favorite_movie($movie_id, &$plugin_cookies)
    {
        $this->session->api_vod_favadd($movie_id);
    }

    protected function do_remove_favorite_movie($movie_id, &$plugin_cookies)
    {
        $this->session->api_vod_favsub($movie_id);
    }

    ///////////////////////////////////////////////////////////////////////
    // Genres.

    protected function load_genres(&$plugin_cookies)
    {
        $doc = $this->session->api_vod_genres();

        $genres = array();
        foreach ($doc->genres as $genre)
            $genres[$genre->id] = $genre->name;

        return $genres;
    }

    public function get_genre_icon_url($genre_id)
    {
        return $this->session->get_icon('mov_genre_default.png');
    }

    public function get_genre_media_url_str($genre_id)
    {
        return KtvVodListScreen::get_media_url_str('genres', $genre_id);
    }

    ///////////////////////////////////////////////////////////////////////
    // Search.

    public function get_search_media_url_str($pattern)
    {
        return KtvVodListScreen::get_media_url_str('search', $pattern);
    }

    ///////////////////////////////////////////////////////////////////////
    // Folder views.

    public function get_vod_genres_folder_views()
    {
        $mov_genre_default_icon_url =
            $this->session->get_icon('mov_genre_default.png');

        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => false,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 3,
                    ViewParams::num_rows => 10,
#                    ViewParams::content_box_padding_bottom => 110,
                ),

                PluginRegularFolderView::base_view_item_params => array
                (
                    ViewItemParams::item_padding_top => 0,
                    ViewItemParams::item_padding_bottom => 0,
                    ViewItemParams::icon_valign => VALIGN_CENTER,
                    ViewItemParams::item_layout => HALIGN_LEFT,
                    ViewItemParams::item_caption_width => 365,
                    ViewItemParams::icon_dx => 65,
                ),

                PluginRegularFolderView::not_loaded_view_item_params => array
                (
                    ViewItemParams::icon_path => $mov_genre_default_icon_url,
                ),
            ));
    }

    public function get_vod_list_folder_views()
    {
        $mov_unset_url = $this->session->get_icon('mov_unset.png');

        return array(
            array
            (
                PluginRegularFolderView::async_icon_loading => true,

                PluginRegularFolderView::view_params => array
                (
                    ViewParams::num_cols => 6,
                    ViewParams::num_rows => 3,
                    ViewParams::icon_selection_box_width => 150,
                    ViewParams::icon_selection_box_height => 222,
                    ViewParams::paint_details => true,
                    ViewParams::zoom_detailed_icon => true,
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
                    ViewItemParams::icon_path => $mov_unset_url,
                    ViewItemParams::item_detailed_icon_path => 'missing://',
                ),
            ));
    }

    public function get_archive(MediaURL $media_url)
    {
        return $this->session->get_archive();
    }

    public function folder_entered(MediaURL $media_url, &$plugin_cookies)
    {
        $this->session->ensure_logged_in($plugin_cookies);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
