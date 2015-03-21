<?php get_header(); ?>
<?php  tfuse_shortcode_content('before'); ?>
<?php 
    $term = get_term_by('slug', get_query_var('term'), get_query_var('taxonomy'));
    $title = tfuse_options('category_title','',$term->term_id);
?>
<div id="main" class="site-main" role="main">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-xs-12 content-area">
                <div class="inner">
                    <article class="post-events">
                        <header class="entry-header">
                            <h1 class="entry-title"><?php echo (!empty($title)) ? $title : single_term_title(); ?></h1>
                            <?php if(tfuse_options('rss_enable','',$term->term_id)):?>
                                <a href="<?php echo tfuse_options('feedburner_url');?>" class="btn btn-transparent btn-join-us btn-rss-subscribe"><span><?php _e('Subscribe to RSS feed','tfuse');?></span></a>
                            <?php endif;?>
                        </header>
                        <div class="entry-content">
                            <?php if (have_posts()) 
                             { ?>

                                <div class="wrapp_calendar">
                                    <div id="calendar" class="calendar"></div>
                                </div>

                                <div class="calendar-navigation">
                                    <a href="#" class="prev" data-calendar-nav="prev"><span><?php _e('Previous Month', 'tfuse'); ?></span></a>
                                    <h3></h3>
                                    <a href="#" class="next" data-calendar-nav="next"><span><?php _e('Next Month', 'tfuse'); ?></span></a>
                                </div>

                                <!-- Modal Window -->
                                <div class="modal fade" id="events-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-body" style="height: 400px"></div>
                                            <button type="button" class="btn" data-dismiss="modal">&times;</button>
                                        </div>
                                    </div>
                                </div>
                                <!--/ Modal Window -->

                           <?php  } 
                             else 
                             { ?>
                                 <h5><?php _e('Sorry, no posts matched your criteria.', 'tfuse'); ?></h5>
                       <?php } ?>
                        </div>
                        <div class="entry-content">
                            <?php
                // Set up and call our Eventbrite query.
                $events = new Eventbrite_Query( apply_filters( 'eventbrite_query_args', array(
                    // 'display_private' => false, // boolean
                    // 'limit' => null,            // integer
                    // 'organizer_id' => null,     // integer
                    // 'p' => null,                // integer
                    // 'post__not_in' => null,     // array of integers
                    // 'venue_id' => null,         // integer
                ) ) );

                if ( $events->have_posts() ) :
                    while ( $events->have_posts() ) : $events->the_post(); ?>

                        <article id="event-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <header class="entry-header">
                                <?php the_post_thumbnail(); ?>

                                <?php the_title( sprintf( '<h1 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h1>' ); ?>

                                <div class="entry-meta">
                                    <?php eventbrite_event_meta(); ?>
                                </div><!-- .entry-meta -->
                            </header><!-- .entry-header -->

                            <div class="entry-content">
                                <?php eventbrite_ticket_form_widget(); ?>
                            </div><!-- .entry-content -->

                            <footer class="entry-footer">
                                <?php eventbrite_edit_post_link( __( 'Edit', 'eventbrite_api' ), '<span class="edit-link">', '</span>' ); ?>
                            </footer><!-- .entry-footer -->
                        </article><!-- #post-## -->

                    <?php endwhile;

                    // Previous/next post navigation.
                    eventbrite_paging_nav( $events );

                else :
                    // If no content, include the "No posts found" template.
                    get_template_part( 'content', 'none' );

                endif;

                // Return $post to its rightful owner.
                wp_reset_postdata();
            ?>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" value="<?php echo $term->term_id; ?>" name="current_event" />
<?php  tfuse_shortcode_content('after'); ?>
<?php get_footer();