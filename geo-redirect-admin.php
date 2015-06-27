<?php
/**
 * Geo Redirect Admin functions
 */

class WP_Geo_Redirect_Admin {

  private $options;

  public function __construct() {
    $this->options = get_option( 'wp_geo_redirect_data' );

    if ( function_exists( 'add_submenu_page' ) ) {
      add_submenu_page( 'options-general.php', __( 'Geo Redirect' ), __( 'Geo Redirect' ), 'manage_options', 'wp-geo-redirect', array( $this, 'display_page' ) );
    }
  }

  public function display_page() {
    $this->add_javascript();
    $this->add_styles();

    $html = '';

    if ( isset( $_POST['submit'] ) && check_admin_referer( 'submit_wp_geo_redirect_x','wp_geo_redirect_nonce_y' ) )
      $this->save();
    
    if ( !empty( $_POST['submit'] ) )
      $html .= '<div id="message" class="updated fade"><p><strong>' . __( 'Options saved.' ) . '</strong></p></div>';
    
    $html .= '
      <div class="wrap geo-redirect-wrap">
      <h2>' . __( 'Geo Redirect' ) . '</h2>
      <form action="" method="post" enctype="multipart/form-data">';
    
    $redirect = '';
    $only_outsite = 0;
    $only_root = 0;
    $only_once = 0;
    $always_root = 1;
    $lang_slug = 'lang';

    if ( $this->options === false ) {
      add_option( 'geo_redirect_data', '' );
    } elseif ( is_array( $this->options ) ) {
      $redirect = ( isset( $this->options['redirect'] ) ? $this->options['redirect'] : $redirect );
      $only_outsite = ( isset( $this->options['only_outsite'] ) ? $this->options['only_outsite'] : $only_outsite );
      $only_root = ( isset( $this->options['only_root'] ) ? $this->options['only_root'] : $only_root );
      $only_once = ( isset( $this->options['only_once'] ) ? $this->options['only_once'] : $only_once );
      $always_root = ( isset( $this->options['always_root'] ) ? $this->options['always_root'] : $always_root );
      $lang_slug = ( isset( $this->options['lang_slug'] ) ? $this->options['lang_slug'] : $lang_slug );
    }

    $geoip = new GeoIP();
    $countries = $geoip->GEOIP_COUNTRY_NAMES;
    $country_codes = $geoip->GEOIP_COUNTRY_CODES;
    $lang_codes = $geoip->GEOIP_LANG_CODES;

    if ( is_array( $countries ) ) {
      asort($countries);
      $html .= '<div class="tablenav top">';
      $html .= '<div class="alignleft actions">';
      $html .= '<select class="countries" name="countries[]">';
        foreach ( $countries as $country_id => $country ) {
          if ( $country_id == 0 ) {
            $html .= '<option value="' . $country_id . '">' . __( 'Select country' ) . '</option>';
          } elseif ( !in_array( $country_id, array( 1, 2 ) ) ) {
            $html .= '<option value="' . $country_id . '" data-lang="' . strtolower( $lang_codes[$country_id] ) . '" data-country-code="' . strtolower( $country_codes[$country_id] ) . '">' . htmlspecialchars( $country ) . '</option>';
          }
        }
      $html .= '</select>';
      $html .= '<input onclick="return geoRedirect.addCountry();" type="submit" class="button-secondary action" value="Add country" />';
      $html .= '</div></div><br clear="all" />';
    }

    $html .= '<div class="geo-redirect-options">
              <table class="wp-list-table widefat striped plugins" cellspacing="0">
                <thead>
                  <tr>
                    <th scope="col" id="country" class="manage-column column-country" width="20%">' . __( 'Country' ) . '</th>
                    <th scope="col" id="option" class="manage-column column-option" width="20%">' . __( 'Redirect Option' ) . '</th>
                    <th scope="col" id="value" class="manage-column column-value" width="55%">' . __( 'Value' ) . '</th>
                    <th scope="col" id="actions" class="manage-column column-actions" width="5%"></th>
                  </tr>
                </thead>
                <tbody>';

    $default_redirect = array(
      'country_id' => -1,
      'redirect_option' => -1,
      'lang_code' => '',
      'domain' => '',
      'pretty' => 0,
      'url' => ''
    );

    if ( is_array( $redirect ) ) {
      foreach ( $redirect as $data ) {
        if ( $data['country_id'] == $default_redirect['country_id'] ) {
          $default_redirect = $data;
          continue;
        }

        $html .='<tr class="geo-redirect-option">'.
                  '<td>'.
                    '<input type="hidden" name="country_ids[]" value="'.$data['country_id'].'"/>'.
                    '<span class="row-title" style="margin-top:4px;display:inline-block;">'.$countries[$data['country_id']].'</span>'.
                  '</td>'.
                  '<td>'.
                      '<select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">'.
                          '<option value="1" ' . (($data['redirect_option'] == 1) ? 'selected="selected"' : '') . ' >' . __( 'Language Code' ) . '</option>'.
                          '<option value="2" ' . (($data['redirect_option'] == 2) ? 'selected="selected"' : '') . ' >' . __( 'Domain Name' ) . '</option>'.
                          '<option value="3" ' . (($data['redirect_option'] == 3) ? 'selected="selected"' : '') . ' >' . __( 'Static URL' ) . '</option>'.
                      '</select>'.
                  '</td>'.
                  '<td id="redirect_options_container_' . $data['country_id'] . '">'.
                    '<span id="redirect_option_value_' . $data['country_id'] . '_1" style="display:' . (($data['redirect_option'] == 1 || empty($data['redirect_option'])) ? 'block' : 'none') . '"><input class="input-text" name="lang_codes[]" type="text" maxlength="5" value="'.stripslashes($data['lang_code']).'"/>&nbsp;' . $this->pretty_permalink_checkbox($data) . '</span>'.
                    '<span id="redirect_option_value_' . $data['country_id'] . '_2" style="display:' . (($data['redirect_option'] == 2) ? 'block' : 'none') . '"><input class="regular-text" name="domains[]" type="text" value="'.stripslashes($data['domain']).'"/></span>'.
                    '<span id="redirect_option_value_' . $data['country_id'] . '_3" style="display:' . (($data['redirect_option'] == 3) ? 'block' : 'none') . '"><input class="regular-text" name="urls[]" type="text" value="'.stripslashes($data['url']).'"/></span>'.
                  '</td>'.
                  '<td>'.
                    '<a onclick="return geoRedirect.removeCountry(this);" href="#" class="delete">' . __( 'Remove' ) . '</a>'.
                  '</td>'.
                '</tr>';
      }
    }


    $html .='<tr class="geo-redirect-option default">'.
              '<td>'.
                  '<input type="hidden" name="country_ids[]" value="' . $default_redirect['country_id'] . '"/>'.
                  '<span class="row-title" style="margin-top:4px;display:inline-block;">' . __( 'Default redirect' ) . '</span>'.
              '</td>'.
              '<td>'.
                '<select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">'.
                    '<option value="-1" ' . (($default_redirect['redirect_option'] == -1) ? 'selected="selected"' : '') . ' >' . __( 'None' ) . '</option>'.
                    '<option value="1" ' . (($default_redirect['redirect_option'] == 1) ? 'selected="selected"' : '') . ' >' . __( 'Language Code' ) . '</option>'.
                    '<option value="2" ' . (($default_redirect['redirect_option'] == 2) ? 'selected="selected"' : '') . ' >' . __( 'Domain Name' ) . '</option>'.
                    '<option value="3" ' . (($default_redirect['redirect_option'] == 3) ? 'selected="selected"' : '') . ' >' . __( 'Static URL' ) . '</option>'.
                '</select>'.
              '</td>'.
              '<td id="redirect_options_container_' . $default_redirect['country_id'] . '">'.
                '<span id="redirect_option_value_' . $default_redirect['country_id'] . '_1" style="display:' . (($default_redirect['redirect_option'] == 1) ? 'block' : 'none') . '"><input class="input-text" name="lang_codes[]" type="text" maxlength="5" value="'.stripslashes($default_redirect['lang_code']).'"/>&nbsp;' . $this->pretty_permalink_checkbox($default_redirect) . '</span>'.
                  '<span id="redirect_option_value_' . $default_redirect['country_id'] . '_2" style="display:' . (($default_redirect['redirect_option'] == 2) ? 'block' : 'none') . '"><input class="regular-text" name="domains[]" type="text" value="'.stripslashes($default_redirect['domain']).'"/></span>'.
                  '<span id="redirect_option_value_' . $default_redirect['country_id'] . '_3" style="display:' . (($default_redirect['redirect_option'] == 3) ? 'block' : 'none') . '"><input class="regular-text" name="urls[]" type="text" value="'.stripslashes($default_redirect['url']).'"/></span>'.
              '</td>'.
              '<td>'.
              '</td>'.
            '</tr>';

    $html .= '</tbody>
      </table>
    </div>';
    
    $html .= '<br clear="all" />';

    $html .= '<table class="wp-list-table widefat plugins" cellspacing="0">
          <thead>
            <tr>
              <th scope="col" id="name" class="manage-column column-name" style="">' . __( 'Language URL variable' ) . '</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <input class="input-text" name="lang_slug" value="'.$lang_slug.'" type="text"/> <span class="example">Example: '.get_home_url().'/?page_id=10&<strong>lang</strong>=en</span>
              </td>
            </tr>
          </tbody>
        </table>';

    $html .= '<br clear="all" />';

    $html .= '<label><input type="checkbox" name="only_outsite" value="1" ' . ( ( $only_outsite == 1 ) ? 'checked="checked"' : '' ) . '/> ' . __( 'Redirect only visitors who come from another site by link' ) . '</label>';

    $html .= '<br clear="all" />';

    $html .= '<label><input type="checkbox" name="only_root" value="1" ' . ( ( $only_root == 1 ) ? 'checked="checked"' : '' ) . '/> ' . __( 'Redirect only visitors of  the site\'s root' ) . '</label>';

    $html .= '<br clear="all" />';

    $html .= '<label><input type="checkbox" name="only_once" value="1" ' . ( ( $only_once == 1 ) ? 'checked="checked"' : '' ) . '/> ' . __( 'Redirect once' ) . '</label>';

    $html .= '<br clear="all" />';

    $html .= '<label><input type="checkbox" name="always_root" value="1" ' . ( ( $always_root == 1 ) ? 'checked="checked"' : '' ) . '/> ' . __( 'Always redirect visitors of the site\'s root' ) . '</label>'; 

    $html .= '  <p class="submit">
            <input type="submit" name="submit" class="button-primary" value="' . __( 'Save Changes' ) . '">
          </p>'; 

    $html .= wp_nonce_field( 'submit_wp_geo_redirect_x', 'wp_geo_redirect_nonce_y', true, false );
    $html .= '</form></div>';
    echo $html;
  }

  private function pretty_permalink_checkbox( $data ) {
    if ($data['lang_code'] == '') {
      $data['lang_code'] = 'some_lang';
    }
    $html = '<label class="pretty-permalink"><span>' . __( 'Use pretty permalink' ) . '</span> <input type="checkbox" name="pretties[]" value="' . $data['country_id'] . '" ' . (($data['pretty'] == 1) ? 'checked="checked"' : '') . '></label>';
    return $html;
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
          var option_html = '<tr class="geo-redirect-option">'+
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
          setTimeout(function() {
            j(option).parents('.geo-redirect-option').remove();
          }, 500);
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
    $always_root        = intval( @$_POST['always_root'] );
    $lang_slug        = ( trim( @$_POST['lang_slug'] ) != '' ) ? (string) urlencode( strtolower( trim( $_POST['lang_slug'] ) ) ) : 'lang';

    if ( count( $country_ids ) > 0 ) {
      $redirect = array();
      foreach ( $country_ids as $key => $country_id ) {
        $domain = (string) htmlspecialchars( strtolower( rtrim( trim( strip_tags( $domains[$key] ) ),'/') ) );
        if ($domain != '') {
          $domain_url_parsed = parse_url($domain);
          $domain = $domain_url_parsed['scheme'] . '://' . $domain_url_parsed['host'];
        }

        $redirect[] = array(
          'country_id'      => intval( $country_id ),
          'redirect_option' => intval( $redirect_options[$key] ),
          'lang_code'       => (string) htmlspecialchars( strtolower( trim( strip_tags( $lang_codes[$key] ) ) ) ),
          'pretty'          => ( in_array( intval( $country_id ), $pretties) ) ? 1 : 0,
          'domain'          => $domain,
          'url'             => (string) htmlspecialchars( trim( strip_tags( $urls[$key] ) ) ) );
        
      }
      
    } else {
      $redirect = ''; 
    }
    
    $data = array(
      'redirect'      => $redirect,
      'only_outsite'  => $only_outsite,
      'only_root'     => $only_root,
      'only_once'     => $only_once,
      'always_root'   => $always_root,
      'lang_slug'     => $lang_slug
    );

    update_option( 'wp_geo_redirect_data', $data );
  }

}

function wp_geo_redirect_init() {
  $geo_admin = new WP_Geo_Redirect_Admin();
}

add_action( 'admin_menu', 'wp_geo_redirect_init' );
