<?php
/**
 * Geo Redirect Admin functions
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

class WP_Geo_Redirect_Admin {

  var $option_field;
  var $options;
  var $page_id;

  public static $defaults = array(
    'redirect'      => array(),
    'only_outsite'  => 0,
    'only_root'     => 0,
    'only_once'     => 0,
    'always_root'   => 1,
    'lang_slug'     => 'lang'
  );

  public function __construct() {

    $this->page_id = 'wp-geo-redirect';
    $this->option_field = 'wp_geo_redirect_data';
    $this->options = get_option( $this->option_field );

    load_plugin_textdomain( 'wp-geo-redirect', plugin_dir_path( __FILE__ ) . 'lang', basename( dirname( __FILE__ ) ) . '/lang' );

    add_action( 'admin_init', array( $this, 'admin_init'), 10 );
    add_action( 'admin_menu', array( $this, 'admin_menu'), 20 );

  }

  function admin_init() {

    add_option( $this->option_field, WP_Geo_Redirect_Admin::$defaults );

  }

  function admin_menu() {

    if ( function_exists( 'add_submenu_page' ) ) {
      add_submenu_page( 'options-general.php', __( 'Geo Redirect', 'wp-geo-redirect' ), __( 'Geo Redirect', 'wp-geo-redirect' ), 'manage_options', $this->page_id, array( $this, 'render' ) );
    }

  }

  public function render() {

    if ( isset( $_POST['submit'] ) && check_admin_referer( 'submit_wp_geo_redirect_x', 'wp_geo_redirect_nonce_y' ) ) {
      $this->save();
    }

    $geoip = new GeoIP();
    $countries = $geoip->GEOIP_COUNTRY_NAMES;
    $country_codes = $geoip->GEOIP_COUNTRY_CODES;
    $lang_codes = $geoip->GEOIP_LANG_CODES;
    
    $this->add_javascript();
    $this->add_styles();

    $this->options = get_option( $this->option_field );
    $html = '';
    ?>

    <?php if ( ! empty( $_POST['submit'] ) ) : ?>
      <div id="message" class="updated fade"><p><strong><?php _e( 'Options saved.', 'wp-geo-redirect' ); ?></strong></p></div>
    <?php endif; ?>

    <div class="wrap geo-redirect-wrap">

      <h2><?php _e( 'Geo Redirect', 'wp-geo-redirect' ); ?></h2>

      <form action="" method="post" enctype="multipart/form-data">

        <?php if ( is_array( $countries ) ) : ?>

          <?php asort($countries); ?>

          <div class="tablenav top">

            <div class="alignleft actions">

              <select class="countries" name="countries[]">
                <?php foreach ( $countries as $country_id => $country ) :
                  if ( $country_id == 0 ) : ?>
                    <option value="<?php echo $country_id; ?>"><?php _e( 'Select country', 'wp-geo-redirect' ); ?></option>
                  <?php elseif ( ! in_array( $country_id, array( 1, 2 ) ) ) : ?>
                    <option value="<?php echo $country_id; ?>" data-lang="<?php echo strtolower( $lang_codes[$country_id] ); ?>" data-country-code="<?php echo strtolower( $country_codes[$country_id] ); ?>">
                      <?php echo htmlspecialchars( $country ); ?>
                    </option>
                  <?php endif;
                endforeach ?>
              </select>

              <input onclick="return geoRedirect.addCountry();" type="submit" class="button-secondary action" value="Add country" />

            </div>

          </div>

          <br clear="all" />

        <?php endif; ?>

        <div class="geo-redirect-options">

          <table class="wp-list-table widefat striped plugins" cellspacing="0">

            <thead>
              <tr>
                <th scope="col" id="country" class="manage-column column-country" width="20%">
                  <?php _e( 'Country', 'wp-geo-redirect' ); ?>
                </th>
                <th scope="col" id="option" class="manage-column column-option" width="20%">
                  <?php _e( 'Redirect Option', 'wp-geo-redirect' ); ?>
                </th>
                <th scope="col" id="value" class="manage-column column-value" width="55%">
                  <?php _e( 'Value', 'wp-geo-redirect' ); ?>
                </th>
                <th scope="col" id="actions" class="manage-column column-actions" width="5%">
                  
                </th>
              </tr>
            </thead>

            <tbody>

              <?php 

              $default_redirect = array(
                'country_id' => -1,
                'redirect_option' => -1,
                'lang_code' => '',
                'domain' => '',
                'pretty' => 0,
                'url' => ''
              );

              foreach ( $this->options['redirect'] as $data ) :

                if ( $data['country_id'] == $default_redirect['country_id'] ) {
                  $default_redirect = $data;
                  continue;
                }

                ?>

                <tr class="geo-redirect-option">
                  <td>
                    <input type="hidden" name="country_ids[]" value="<?php echo $data['country_id']; ?>">
                    <span class="row-title" style="margin-top:4px;display:inline-block;">
                      <?php echo $countries[ $data['country_id'] ]; ?>
                    </span>
                  </td>
                  <td>
                      <select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">
                        <option value="1"<?php echo ( ( $data['redirect_option'] == 1 ) ? ' selected="selected"' : '' ); ?>>
                          <?php _e( 'Language Code', 'wp-geo-redirect' ); ?>
                        </option>
                        <option value="2"<?php echo ( ( $data['redirect_option'] == 2 ) ? ' selected="selected"' : '' ); ?>>
                          <?php _e( 'Domain Name', 'wp-geo-redirect' ); ?>
                        </option>
                        <option value="3"<?php echo ( ( $data['redirect_option'] == 3 ) ? ' selected="selected"' : '' ); ?>>
                          <?php _e( 'Static URL', 'wp-geo-redirect' ); ?>
                        </option>
                      </select>
                  </td>
                  <td id="redirect_options_container_<?php echo $data['country_id']; ?>">
                    <span id="redirect_option_value_<?php echo $data['country_id']; ?>_1" style="display:<?php echo ( ( $data['redirect_option'] == 1 || empty( $data['redirect_option'] ) ) ? 'block' : 'none' ); ?>">
                      <input class="input-text" name="lang_codes[]" type="text" maxlength="5" value="<?php echo stripslashes( $data['lang_code'] ); ?>">
                      <?php echo $this->pretty_permalink_checkbox($data); ?>
                    </span>
                    <span id="redirect_option_value_<?php echo $data['country_id']; ?>_2" style="display:<?php echo ( ( $data['redirect_option'] == 2 ) ? 'block' : 'none' ); ?>">
                      <input class="regular-text" name="domains[]" type="text" value="<?php echo stripslashes( $data['domain'] ); ?>">
                    </span>
                    <span id="redirect_option_value_<?php echo $data['country_id']; ?>_3" style="display:<?php echo ( ( $data['redirect_option'] == 3 ) ? 'block' : 'none' ); ?>">
                      <input class="regular-text" name="urls[]" type="text" value="<?php echo stripslashes( $data['url'] ); ?>">
                    </span>
                  </td>
                  <td>
                    <a onclick="return geoRedirect.removeCountry(this);" href="#" class="delete"><?php _e( 'Remove', 'wp-geo-redirect' ); ?></a>
                  </td>
                </tr>
              <?php endforeach; ?>


              <tr class="geo-redirect-option default">
                <td>
                  <input type="hidden" name="country_ids[]" value="<?php echo $default_redirect['country_id']; ?>">
                  <span class="row-title" style="margin-top:4px;display:inline-block;">
                    <?php _e( 'Default redirect', 'wp-geo-redirect' ); ?>
                  </span>
                </td>
                <td>
                  <select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">
                    <option value="-1"<?php echo ( ( $default_redirect['redirect_option'] == -1 ) ? ' selected="selected"' : '' ); ?>>
                      <?php _e( 'None', 'wp-geo-redirect' ); ?>
                    </option>
                    <option value="1"<?php echo ( ( $default_redirect['redirect_option'] == 1 ) ? ' selected="selected"' : '' ); ?>>
                      <?php _e( 'Language Code', 'wp-geo-redirect' ); ?>
                    </option>
                    <option value="2"<?php echo ( ( $default_redirect['redirect_option'] == 2 ) ? ' selected="selected"' : '' ); ?>>
                      <?php _e( 'Domain Name', 'wp-geo-redirect' ); ?>
                    </option>
                    <option value="3"<?php echo ( ( $default_redirect['redirect_option'] == 3 ) ? ' selected="selected"' : '' ); ?>>
                      <?php _e( 'Static URL', 'wp-geo-redirect' ); ?>
                    </option>
                  </select>
                </td>
                <td id="redirect_options_container_<?php echo $default_redirect['country_id']; ?>">
                  <span id="redirect_option_value_<?php echo $default_redirect['country_id']; ?>_1" style="display:<?php echo ( ( $default_redirect['redirect_option'] == 1 ) ? 'block' : 'none' ); ?>">
                    <input class="input-text" name="lang_codes[]" type="text" maxlength="5" value="<?php echo stripslashes( $default_redirect['lang_code'] ); ?>">
                    <?php echo $this->pretty_permalink_checkbox( $default_redirect ); ?>
                  </span>
                  <span id="redirect_option_value_<?php echo $default_redirect['country_id']; ?>_2" style="display:<?php echo ( ( $default_redirect['redirect_option'] == 2 ) ? 'block' : 'none' ); ?>">
                    <input class="regular-text" name="domains[]" type="text" value="<?php echo stripslashes( $default_redirect['domain'] ); ?>">
                  </span>
                  <span id="redirect_option_value_<?php echo $default_redirect['country_id']; ?>_3" style="display:<?php echo ( ( $default_redirect['redirect_option'] == 3 ) ? 'block' : 'none' ); ?>">
                    <input class="regular-text" name="urls[]" type="text" value="<?php echo stripslashes( $default_redirect['url'] ); ?>">
                  </span>
                </td>
                <td>
                </td>
              </tr>

            </tbody>

          </table>

        </div>
        
        <br clear="all" />

        <table class="wp-list-table widefat plugins" cellspacing="0">

          <thead>
            <tr>
              <th scope="col" id="name" class="manage-column column-name">
                <?php _e( 'Language URL variable', 'wp-geo-redirect' ); ?>
              </th>
            </tr>
          </thead>

          <tbody>
            <tr>
              <td>
                <input class="input-text" name="lang_slug" value="<?php echo $this->options['lang_slug']; ?>" type="text">
                <span class="example">Example: <?php echo get_home_url(); ?>/?page_id=10&<strong>lang</strong>=en</span>
              </td>
            </tr>
          </tbody>

        </table>

        <br clear="all" />

        <label>
          <input type="checkbox" name="only_outsite" value="1"<?php echo ( ( $this->options['only_outsite'] == 1 ) ? ' checked="checked"' : '' ); ?>>
          <?php _e( 'Redirect only visitors who come from another site by link', 'wp-geo-redirect' ); ?>
        </label>

        <br clear="all" />

        <label>
          <input type="checkbox" name="only_root" value="1" <?php echo ( ( $this->options['only_root'] == 1 ) ? 'checked="checked"' : '' ); ?>>
          <?php _e( 'Redirect only visitors of  the site\'s root', 'wp-geo-redirect' ); ?>
        </label>

        <br clear="all" />

        <label>
          <input type="checkbox" name="only_once" value="1" <?php echo ( ( $this->options['only_once'] == 1 ) ? 'checked="checked"' : '' ); ?>>
          <?php _e( 'Redirect once', 'wp-geo-redirect' ); ?>
        </label>

        <br clear="all" />

        <label>
          <input type="checkbox" name="always_root" value="1" <?php echo ( ( $this->options['always_root'] == 1 ) ? 'checked="checked"' : '' ); ?>>
          <?php _e( 'Always redirect visitors of the site\'s root', 'wp-geo-redirect' ); ?>
        </label> 

        <p class="submit">
          <input type="submit" name="submit" class="button-primary" value="<?php _e( 'Save Changes', 'wp-geo-redirect' ); ?>">
        </p>

        <?php echo wp_nonce_field( 'submit_wp_geo_redirect_x', 'wp_geo_redirect_nonce_y', true, false ); ?>
      </form>
    </div>
  <?php
  }

  private function pretty_permalink_checkbox( $data ) {
    if ($data['lang_code'] == '') {
      $data['lang_code'] = 'some_lang';
    } ?>
    
    <label class="pretty-permalink">
      <span>
        <?php _e( 'Use pretty permalink', 'wp-geo-redirect' ); ?>
      </span>
      <input type="checkbox" name="pretties[]" value="<?php echo $data['country_id']; ?>"<?php echo ( ( $data['pretty'] == 1 ) ? ' checked="checked"' : '' ); ?>>
    </label>
    <?php
  }

  private function add_styles() {
    ?>
    <style>
      .geo-redirect-option .row-title {
        margin-top: 4px;
        display: inline-block;
      }
      .geo-redirect-option .delete {
        margin-top: 4px;
        display: inline-block;
        font-weight: bold;
      }
      .geo-redirect-wrap .input-text {
        width: 60px;
      }
      .geo-redirect-wrap .example {
        color: #999;
        display: inline-block;
        margin-left: 8px;
      }
      .geo-redirect-option .pretty-permalink {
        display: inline-block;
        margin-left: 8px;
        margin-top: -3px;
      }
      .geo-redirect-option .pretty-permalink span {
        display: inline-block;
      }
      .geo-redirect-option .pretty-permalink input {
        float: left;
        margin-right: 3px;
        margin-top: 1px;
      }
    </style>
    <?php
  }

  private function add_javascript() {
    $site_url_parsed = parse_url( get_home_url() );
    ?>
    <script type="text/javascript">
      var j = jQuery;
      var geoRedirect = {
        url_scheme: '<?php echo $site_url_parsed["scheme"]; ?>',
        domain_url: '<?php echo $site_url_parsed["host"]; ?>',
        home_url: '<?php echo get_home_url(); ?>',

        addCountry: function() {
          var exist = false;
          var country_id = j('select.countries').val();
          country_id = parseInt(country_id, 10);

          if (country_id === 0 || isNaN(country_id))
            return false;

          j('.geo-redirect-options input[name="country_ids[]"]').each(function(index) {
            if (j(this).val() == country_id) {
              exist = true;
              j('#redirect_options_container_'+country_id).parents('.geo-redirect-option').find('select.redirect_options').focus();
            }

          });

          if (exist === true)
            return false;

          var country_name = j('select.countries option:selected').text();
          var lang_code = j('select.countries option:selected').attr('data-lang');
          var country_code = j('select.countries option:selected').attr('data-country-code');
          var option_html = '<tr class="geo-redirect-option" style="display:none;">'+
                              '<td>'+
                                '<input type="hidden" name="country_ids[]" value="'+country_id+'"/>'+
                                '<span class="row-title" style="margin-top:4px;display:inline-block;">'+country_name+'</span>'+
                              '</td>'+
                              '<td>'+
                                  '<select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">'+
                                      '<option value="1" >Language Code</option>'+
                                      '<option value="2" >Domain Name</option>'+
                                      '<option value="3" >Static URL</option>'+
                                  '</select>'+
                              '</td>'+
                              '<td id="redirect_options_container_'+country_id+'">'+
                                '<span id="redirect_option_value_'+country_id+'_1" style="display:block"><input class="input-text" name="lang_codes[]" type="text" maxlength="5" value="'+lang_code+'"/>&nbsp;<label class="pretty-permalink"><span>Use pretty permalink</span> <input type="checkbox" name="pretties[]" value="'+country_id+'"></label></span>'+
                                  '<span id="redirect_option_value_'+country_id+'_2" style="display:none"><input class="regular-text" name="domains[]" type="text" value="'+geoRedirect.url_scheme+'://'+country_code+'.'+geoRedirect.domain_url+'"/></span>'+
                                  '<span id="redirect_option_value_'+country_id+'_3" style="display:none"><input class="regular-text" name="urls[]" type="text" value="'+geoRedirect.home_url+'/'+country_code+'_visitors_sample_page/"/></span>'+
                              '</td>'+
                              '<td>'+
                                '<a onclick="return geoRedirect.removeCountry(this);" href="#" class="delete">Remove</a>'+
                              '</td>'+
                            '</tr>';

          j('.geo-redirect-options table tbody').prepend(option_html);
          j('.geo-redirect-options table tbody tr:first-child').fadeIn();

          return false;
        },

        clearCountry: function(option) {
          var inputs = j(option).parents('.geo-redirect-option').find('input:visible');
          j(inputs).each(function(){
            j(this).val('');
          });
          return false;
        },

        removeCountry: function(option) {
          j(option).parents('.geo-redirect-option').fadeOut(function() {
            j(this).remove();
          });
          return false;
        },

        switchOption: function(select){
          var option_id = j(select).val();
          var country_id = j(select).parents('.geo-redirect-option').find('input[name="country_ids[]"]').val();
          j('#redirect_options_container_'+country_id+' > span').hide();
          j('#redirect_option_value_'+country_id+'_'+option_id).show();
        }
      };
    </script> 
    <?php
  }

  private function save() {
    $country_ids      = (array) @$_POST['country_ids'];
    $redirect_options = (array) @$_POST['redirect_options'];
    $lang_codes       = (array) @$_POST['lang_codes'];
    $pretties         = (array) @$_POST['pretties'];
    $domains          = (array) @$_POST['domains'];
    $urls             = (array) @$_POST['urls'];
    $only_outsite     = intval( @$_POST['only_outsite']) ;
    $only_root        = intval( @$_POST['only_root'] );
    $only_once        = intval( @$_POST['only_once'] );
    $always_root      = intval( @$_POST['always_root'] );
    $lang_slug        = ( trim( @$_POST['lang_slug'] ) != '' ) ? (string) urlencode( strtolower( trim( $_POST['lang_slug'] ) ) ) : 'lang';

    $redirect = array();

    if ( count( $country_ids ) > 0 ) {

      foreach ( $country_ids as $key => $country_id ) {
        $domain = (string) htmlspecialchars( strtolower( rtrim( trim( strip_tags( $domains[$key] ) ),'/') ) );
        if ( $domain != '' ) {
          $domain_url_parsed = parse_url( $domain );
          $domain = $domain_url_parsed['scheme'] . '://' . $domain_url_parsed['host'];
        }

        $redirect[] = array(
          'country_id'      => intval( $country_id ),
          'redirect_option' => intval( $redirect_options[$key] ),
          'lang_code'       => (string) htmlspecialchars( strtolower( trim( strip_tags( $lang_codes[$key] ) ) ) ),
          'pretty'          => ( in_array( intval( $country_id ), $pretties) ) ? 1 : 0,
          'domain'          => $domain,
          'url'             => (string) htmlspecialchars( trim( strip_tags( $urls[$key] ) ) )
        );
      }
      
    }
    
    $data = array(
      'redirect'      => $redirect,
      'only_outsite'  => $only_outsite,
      'only_root'     => $only_root,
      'only_once'     => $only_once,
      'always_root'   => $always_root,
      'lang_slug'     => $lang_slug
    );

    update_option( $this->option_field, $data );
  }

}

/**
 * Initialize the plugin
 */

global $wp_geo_admin_instance;

if ( class_exists('WP_Geo_Redirect_Admin') && ! $wp_geo_admin_instance ) {
  $wp_geo_admin_instance = new WP_Geo_Redirect_Admin();
}
