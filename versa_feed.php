<?php
/**
 * This is the WordPress RSS2 Feed Template, customized for Versa:
 * - updates twice hourly
 * - includes the most recent 20 posts
 * - guid is set to the post ID (to match the set_article value in the front-end JS snippet)
 * - only includes tags needed for Versa scraping
 */

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>

<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
  <?php
  /**
   * Fires at the end of the RSS root to add namespaces.
   *
   * @since 2.0.0
   */
  do_action( 'rss2_ns' );
  ?>
  >

  <channel>
    <title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
    <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
    <link><?php bloginfo_rss('url') ?></link>
    <description><?php bloginfo_rss("description") ?></description>
    <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
    <language><?php bloginfo_rss( 'language' ); ?></language>
    <?php
    $duration = 'hourly';
    /**
     * Filter how often to update the RSS feed.
     *
     * @since 2.1.0
     *
     * @param string $duration The update period.
     *                         Default 'hourly'. Accepts 'hourly', 'daily', 'weekly', 'monthly', 'yearly'.
     */
    ?>
    <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', $duration ); ?></sy:updatePeriod>
    <?php
    $frequency = '2';
    /**
     * Filter the RSS update frequency.
     *
     * @since 2.1.0
     *
     * @param string $frequency An integer passed as a string representing the frequency
     *                          of RSS updates within the update period. Default '1'.
     */
    ?>
    <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', $frequency ); ?></sy:updateFrequency>
    <?php
    /**
     * Fires at the end of the RSS2 Feed Header.
     *
     * @since 2.0.0
     */
    do_action( 'rss2_head');
    query_posts('showposts=20'); /* how many posts to fetch */

    while( have_posts()) : the_post();
      ?>
      <item>
        <title><?php the_title_rss(); ?></title>
        <link><?php the_permalink_rss(); ?></link>
        <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
        <guid isPermaLink="false"><?php echo FeaturedPerspectives::get_post_guid(); ?></guid>
        <?php
        /**
         * Fires at the end of each RSS2 feed item.
         *
         * @since 2.0.0
         */
        do_action( 'rss2_item' );
        ?>
      </item>
      <?php endwhile; ?>
  </channel>
</rss>
