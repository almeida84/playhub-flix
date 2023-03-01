<?php
header('Content-Type: text/xml; charset=UTF8');
$cacheFeedName = "feedCacheRSS" . json_encode($_REQUEST);
$lifetime = 43200;
$feed = ObjectYPT::getCache($cacheFeedName, $lifetime);
if (empty($feed)) {
    _ob_start();
    echo'<?xml version="1.0" encoding="UTF-8"?>'
    ?>
    <rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/"
         xmlns:wfw="http://wellformedweb.org/CommentAPI/"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:atom="http://www.w3.org/2005/Atom"
         xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
         xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
         xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" >
        <channel>
            <atom:link href="<?php echo $global['webSiteRootURL'] . ltrim($_SERVER["REQUEST_URI"], "/"); ?>" rel="self" type="application/rss+xml" />
            <title><![CDATA[ <?php echo feedText($title); ?> ]]></title>
            <description><![CDATA[ <?php echo feedText($description); ?> ]]></description>
            <link><?php echo $link; ?></link>
            <sy:updatePeriod>hourly</sy:updatePeriod>
            <sy:updateFrequency>1</sy:updateFrequency>
            <author><![CDATA[ <?php echo $author; ?> ]]></author>

            <itunes:author><![CDATA[ <?php echo $author; ?> ]]></itunes:author>
            <itunes:summary><![CDATA[ <?php echo feedText($description); ?> ]]></itunes:summary>
            <itunes:owner>
                <itunes:name>
                    <![CDATA[ <?php echo $title; ?> ]]>
                </itunes:name>
                <itunes:email>
                    <![CDATA[ <?php echo $author; ?> ]]>
                </itunes:email>
            </itunes:owner>

            <image>
            <title><![CDATA[ <?php echo feedText($title); ?> ]]></title>
            <url><?php echo $logo; ?></url>
            <link><?php echo $link; ?></link>
            <width>144</width>
            <height>40</height>
            <description>RSS</description>
            </image>

            <?php
            foreach ($rows as $row) {
                $files = getVideosURL($row['filename']);
                $enclosure = '';
                foreach ($files as $value) {
                    if ($value["type"] === "video" && file_exists($value['path'])) {
                        $path_parts = pathinfo($value['path']);
                        $value['mime'] = "video/{$path_parts['extension']}";
                        $value['size'] = filesize($value['path']);
                        // replace to validate
                        $value['url'] = str_replace("http://", "https://", $value['url']);
                        $enclosure = '<enclosure url="' . $value['url'] . '" length="' . $value['size'] . '" type="' . $value['mime'] . '" />';
                        break;
                    }
                } ?>
                <item>
                    <title><![CDATA[ <?php echo feedText($row['title']); ?> ]]></title>
                    <description><![CDATA[ <?php echo feedText($row['description']); ?> ]]></description>
                    <link> <?php echo Video::getLink($row['id'], $row['clean_title']); ?></link>
                    <?php echo $enclosure; ?>
                    <pubDate><?php echo date('r', strtotime($row['created'])); ?></pubDate>
                    <guid><?php echo Video::getLinkToVideo($row['id'], $row['clean_title'], false, "permalink"); ?></guid>
                </item>
                <?php
            } ?>
        </channel>
    </rss>
    <?php
    $feed = ob_get_contents();
    _ob_end_clean();
    ObjectYPT::setCache($cacheFeedName, $feed);
} else {
    //echo '<!-- cache -->';
}
if (!is_string($feed)) {
    $feed = json_encode($feed);
}
echo $feed;
