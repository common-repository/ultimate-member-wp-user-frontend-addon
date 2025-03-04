<?php
class WPUF_Dashboard_new {

    function __construct() {
        add_shortcode( 'wpuf_dashboard_new', array($this, 'shortcode') );
    }

    /**
     * Handle's user dashboard functionality
     *
     * Insert shortcode [wpuf_dashboard] in a page to
     * show the user dashboard
     *
     * @since 0.1
     */
    function shortcode( $atts ) {

        $post_type = wpuf_get_option( 'post_type', 'wpuf_dashboard', 'post' );
        $default   = array('post_type' => $post_type);

        extract( shortcode_atts( $default, $atts ) );

        ob_start();

        if ( is_user_logged_in() ) {
            $this->post_listing( $post_type );
        } else {
            printf( __( "This page is restricted. Please %s to view this page.", 'wpuf' ), wp_loginout( '', false ) );
        }

        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * List's all the posts by the user
     *
     * @global object $wpdb
     * @global object $userdata
     */
    function post_listing( $post_type ) {
        global $wpdb, $userdata, $post;

        $userdata = get_userdata( $userdata->ID );
        $pagenum = isset( $_GET['pagenum'] ) ? intval( $_GET['pagenum'] ) : 1;

        //delete post
        if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == "del" ) {
            $this->delete_post();
        }

        //show delete success message
        if ( isset( $_GET['msg'] ) && $_GET['msg'] == 'deleted' ) {
            echo '<div class="ufa-success">' . __( 'Post Deleted', 'wpuf' ) . '</div>';
        }

        $args = array(
            'author' => get_current_user_id(),
            'post_status' => array('draft', 'future', 'pending', 'publish'),
            'post_type' => $post_type,
            'posts_per_page' => wpuf_get_option( 'per_page', 'wpuf_dashboard', 10 ),
            'paged' => $pagenum
        );

        $dashboard_query = new WP_Query( $args );
        $post_type_obj = get_post_type_object( $post_type );
        ?>
        <?php if ( wpuf_get_option( 'show_post_count', 'wpuf_dashboard', 'on' ) == 'on' ) { ?>
            <div class="post_count"><?php printf( __( 'You have created <span>%d</span> %s', 'wpuf' ), $dashboard_query->found_posts, $post_type_obj->label ); ?></div>
        <?php } ?>

        <?php do_action( 'wpuf_dashboard_top', $userdata->ID, $post_type_obj ) ?>

        <?php if ( $dashboard_query->have_posts() ) { ?>

            <?php
            $featured_img = wpuf_get_option( 'show_ft_image', 'wpuf_dashboard' );
            $featured_img_size = wpuf_get_option( 'ft_img_size', 'wpuf_dashboard' );
            $charging_enabled = wpuf_get_option( 'charge_posting', 'wpuf_payment', 'no' );
                    while ($dashboard_query->have_posts()) {
                        $dashboard_query->the_post();
                        ?>
                            <div class="um-item">
                                <?php if ( 'on' == $featured_img ) { ?>
                                    <div class="um-item-thumbnail-special">
                                        <?php
                                        if ( has_post_thumbnail() ) {
                                            the_post_thumbnail( $featured_img_size );
                                        } else {
                                            printf( '<img src="%1$s" class="attachment-thumbnail wp-post-image" alt="%2$s" title="%2$s" />', apply_filters( 'wpuf_no_image', plugins_url( '/images/no-image.png', __FILE__ ) ), __( 'No Image', 'wpuf' ) );
                                        }
                                        ?>
                                    </div>
                                <?php } ?>
                          			<div class="um-titles">
                                  <div class="um-item-link">
                                    <i class="um-icon-ios-paper"></i>
                                    <?php if ( in_array( $post->post_status, array('draft', 'future', 'pending') ) ) { ?>
                                      <?php the_title(); ?>
                                    <?php } else { ?>
                                      <a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'wpuf' ), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
                                    <?php } ?>
                                  </div>
                            			<div class="um-item-meta">
                            				<span><?php wpuf_show_post_status( $post->post_status ) ?></span>
                                    <?php
                                    if ( $charging_enabled == 'yes' ) {
                                        $order_id = get_post_meta( $post->ID, 'wpuf_order_id', true );
                                        ?>
                                        <span>
                                            <?php if ( $post->post_status == 'pending' && $order_id ) { ?>
                                                <a href="<?php echo trailingslashit( get_permalink( wpuf_get_option( 'payment_page', 'wpuf_payment' ) ) ); ?>?action=wpuf_pay&type=post&post_id=<?php echo $post->ID; ?>">Pay Now</a>
                                            <?php } ?>
                                        </span>
                                    <?php } ?>
                                    <span>
                                        <?php
                                        if ( wpuf_get_option( 'enable_post_edit', 'wpuf_dashboard', 'yes' ) == 'yes' ) {
                                            $disable_pending_edit   = wpuf_get_option( 'disable_pending_edit', 'wpuf_dashboard', 'on' );
                                            $edit_page              = (int) wpuf_get_option( 'edit_page_id', 'wpuf_frontend_posting' );
                                            $post_id                = $post->ID;
                                            $url                    = add_query_arg( array('pid' => $post->ID), get_permalink( $edit_page ) );

                                            $edit_page_url = apply_filters( 'wpuf_edit_post_link', $url );

                                            if ( $post->post_status == 'pending' && $disable_pending_edit == 'on' ) {
                                              // don't show the edit link
                                            } else {
                                              ?>
                                              <a href="<?php echo wp_nonce_url( $edit_page_url, 'wpuf_edit' ); ?>"><?php _e( 'Edit', 'wpuf' ); ?></a>
                                              <?php
                                            }
                                        }
                                        ?>

                                        <?php
										if ( wpuf_get_option( 'enable_post_del', 'wpuf_dashboard', 'yes' ) == 'yes' ) {
											$del_url = add_query_arg( array('action' => 'del', 'pid' => $post->ID) );
											$message = __( 'Are you sure to delete?', 'wpuf' );
											?>
                                            <a href="<?php echo wp_nonce_url( $del_url, 'wpuf_del' ) ?>" onclick="return confirm('<?php echo $message ?>');"><span style="color: red;"><?php _e( 'Delete', 'wpuf' ); ?></span></a>
										<?php } ?>
                                    </span>
                            			</div>
                                </div>
                          	</div>
                    <?php } ?>

            <div class="wpuf-pagination">
                <?php
                $pagination = paginate_links( array(
                    'base' => add_query_arg( 'pagenum', '%#%' ),
                    'format' => '',
                    'prev_text' => __( '&laquo;', 'wpuf' ),
                    'next_text' => __( '&raquo;', 'wpuf' ),
                    'total' => $dashboard_query->max_num_pages,
                    'current' => $pagenum
                        ) );

                if ( $pagination ) {
                    echo $pagination;
                }
                ?>
            </div>

            <?php
        } else {
            printf( __( 'No %s found', 'wpuf' ), $post_type_obj->label );
            do_action( 'wpuf_dashboard_nopost', $userdata->ID, $post_type_obj );
        }

        do_action( 'wpuf_dashboard_bottom', $userdata->ID, $post_type_obj );
        ?>

        <?php
        $this->user_info();

        // clean $dashboard_query from corrupting beyond use
        wp_reset_query();
    }

    /**
     * Show user info on dashboard
     */
    function user_info() {
        global $userdata;

        if ( wpuf_get_option( 'show_user_bio', 'wpuf_dashboard', 'on' ) == 'on' ) {
            ?>
            <div class="wpuf-author">
                <h3><?php _e( 'Author Info', 'wpuf' ); ?></h3>
                <div class="wpuf-author-inside odd">
                    <div class="wpuf-user-image"><?php echo get_avatar( $userdata->user_email, 80 ); ?></div>
                    <div class="wpuf-author-body">
                        <p class="wpuf-user-name"><a href="<?php echo get_author_posts_url( $userdata->ID ); ?>"><?php printf( esc_attr__( '%s', 'wpuf' ), $userdata->display_name ); ?></a></p>
                        <p class="wpuf-author-info"><?php echo $userdata->description; ?></p>
                    </div>
                </div>
            </div><!-- .author -->
            <?php
        }
    }

    /**
     * Delete a post
     *
     * Only post author and editors has the capability to delete a post
     */
    function delete_post() {
        global $userdata;

        $nonce = $_REQUEST['_wpnonce'];
        if ( !wp_verify_nonce( $nonce, 'wpuf_del' ) ) {
            die( "Security check" );
        }

        //check, if the requested user is the post author
        $maybe_delete = get_post( $_REQUEST['pid'] );

        if ( ($maybe_delete->post_author == $userdata->ID) || current_user_can( 'delete_others_pages' ) ) {
            wp_delete_post( $_REQUEST['pid'] );

            //redirect
			$roles = get_option('settings_basic');

			if(is_array($roles)){
				$slugview = $roles['slug_view'];
			}else{
				$slugview = 'my-posts';
			}

            $redirect = add_query_arg( array('msg' => 'deleted', 'profiletab' => $slugview), get_permalink() );
            wp_redirect( $redirect );
        } else {
            echo '<div class="error">' . __( 'You are not the post author. Cheeting huh!', 'wpuf' ) . '</div>';
        }
    }

}

$wpuf_dashboard = new WPUF_Dashboard_new();
?>