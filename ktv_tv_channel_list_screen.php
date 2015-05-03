<?php
///////////////////////////////////////////////////////////////////////////

class KtvTvChannelListScreen extends TvChannelListScreen
{
    private $session;

    public function __construct($session, Tv $tv, $folder_views)
    {
        parent::__construct($tv, $folder_views);

        $this->session = $session;
    }

    public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies)
    {
        $this->session->ensure_logged_in($plugin_cookies);
        $this->tv->ensure_channels_loaded($plugin_cookies);

        return parent::get_all_folder_items($media_url, $plugin_cookies);
    }
}

///////////////////////////////////////////////////////////////////////////
?>
