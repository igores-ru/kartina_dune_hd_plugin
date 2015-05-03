<?php
///////////////////////////////////////////////////////////////////////////

require_once 'lib/vod/vod_list_screen.php';

class KtvVodListScreen extends VodListScreen
{
    public static function get_media_url_str($page_name, $arg = null)
    {
        $arr['screen_id'] = self::ID;
        $arr['page_name'] = $page_name;
        if ($page_name === 'search')
            $arr['pattern'] = $arg;
        else if ($page_name === 'genres')
            $arr['genre_id'] = $arg;
        return MediaURL::encode($arr);
    }

    ///////////////////////////////////////////////////////////////////////

    private $session;

    public function __construct($session, Vod $vod)
    {
        parent::__construct($vod);

        $this->session = $session;
    }

    ///////////////////////////////////////////////////////////////////////

    private function get_page_for_ndx($ndx, $page_size)
    {
        return intval($ndx / $page_size) + 1;
    }

    protected function get_short_movie_range(MediaURL $media_url, $from_ndx,
        &$plugin_cookies)
    {
        $nums = 24;
        $page = $this->get_page_for_ndx($from_ndx, $nums);

        if ($media_url->page_name === 'last')
            $doc = $this->session->api_vod_list_last($page, $nums);
        else if ($media_url->page_name === 'best')
            $doc = $this->session->api_vod_list_best($page, $nums);
        else if ($media_url->page_name === 'search')
        {
            $doc = $this->session->api_vod_list_search(
                $media_url->pattern, $page, $nums);
        }
        else if ($media_url->page_name === 'genres')
        {
            $doc = $this->session->api_vod_list_genres(
                $media_url->genre_id, $page, $nums);
        }
        else
            throw new Exception('Vod list: invalid page name.');

        if (!isset($doc->total))
            throw new Exception('Invalid data returned from server');

        $total = intval($doc->total);
        if ($total === 0)
            return new ShortMovieRange(0, 0);

        $from_ndx = (intval($doc->page) - 1) * $nums;

        $movies = array();
        foreach ($doc->rows as $row)
        {
            $icon_url = 'http://' . KTV::$SERVER . $row->poster;
            $movies[] = new ShortMovie(
                $row->id, $row->name, $icon_url);
        }

        return new ShortMovieRange($from_ndx, $total, $movies);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
