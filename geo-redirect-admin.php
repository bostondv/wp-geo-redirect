<?php
/**
 * Geo Redirect Admin functions
 */


function geo_redirect_admin_page() {
  if ( function_exists( 'add_submenu_page' ) ) {
    add_submenu_page( 'options-general.php', __( 'Geo Redirect' ), __( 'Geo Redirect' ), 'manage_options', 'geo_redirect', 'geo_redirect_admin_page_display' );
  }
}

function geo_redirect_pretty_permalink_checkbox($data){
    if ($data['lang_code'] == '')
        $data['lang_code'] = 'some_lang';
    $html = '<label title="Redirect to ' . get_home_url() . '/' . $data['lang_code'] .'/ instead of ' . get_home_url() . '/?lang=' . $data['lang_code'] . '">Use pretty permalink <input type="checkbox" name="pretties[]" value="' . $data['country_id'] . '" ' . (($data['pretty'] == 1) ? 'checked="checked"' : '') . '></label>';
    return $html;
}

function geo_redirect_admin_page_display(){
  $html = '';

  if ( isset( $_POST['submit'] ) && check_admin_referer( 'submit_geo_redirect_x','geo_redirect_nonce_y' ) )
    geo_redirect_save();
  
  if ( !empty( $_POST['submit'] ) )
    $html .= '<div id="message" class="updated fade"><p><strong>' . __( 'Options saved.' ) . '</strong></p></div>';
  
  $html .= '
    <div class="wrap">
    <h2>' . __( 'Geo Redirect' ) . '</h2>
    <form action="" method="post" enctype="multipart/form-data">';
  
  $redirect = '';
  $only_outsite = 0;
  $only_root = 0;
  $only_once = 0;
  $lang_slug = 'lang';
  
  $geo_redirect_data = get_option( 'geo_redirect_data' );

  if ( $geo_redirect_data === false ) {
    add_option( 'geo_redirect_data', '' );
  } elseif ( is_array( $geo_redirect_data ) ) {
    $redirect = $geo_redirect_data['redirect'];
    $only_outsite = $geo_redirect_data['only_outsite'];
    $only_root = $geo_redirect_data['only_root'];
    $only_once = $geo_redirect_data['only_once'];
    $lang_slug = $geo_redirect_data['lang_slug'];
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
            <table class="wp-list-table widefat plugins" cellspacing="0">
              <thead>
                <tr>
                  <th scope="col" id="name" class="manage-column column-name" width="20%">' . __( 'Country' ) . '</th>
                  <th scope="col" id="name" class="manage-column column-name" width="20%">' . __( 'Redirect Option' ) . '</th>
                  <th scope="col" id="name" class="manage-column column-name" width="55%">' . __( 'Value' ) . '</th>
                  <th scope="col" id="name" class="manage-column column-name" width="5%"></th>
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

      $html .='<tr class="geo-redirect-option active">'.
                '<td>'.
                  '<input type="hidden" name="country_ids[]" value="'.$data['country_id'].'"/>'.
                  '<p><b>'.$countries[$data['country_id']].'</b></p>'.
                '</td>'.
                '<td>'.
                    '<p><select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">'.
                        '<option value="1" ' . (($data['redirect_option'] == 1) ? 'selected="selected"' : '') . ' >' . __( 'Language Code' ) . '</option>'.
                        '<option value="2" ' . (($data['redirect_option'] == 2) ? 'selected="selected"' : '') . ' >' . __( 'Domain Name' ) . '</option>'.
                        '<option value="3" ' . (($data['redirect_option'] == 3) ? 'selected="selected"' : '') . ' >' . __( 'Static URL' ) . '</option>'.
                    '</select></p>'.
                '</td>'.
                '<td id="redirect_options_container_' . $data['country_id'] . '">'.
                  // '<p><input class="small-text" name="lang_codes[]" type="text" maxlength="3" value="'.stripslashes( $data['lang_code'] ).'"/></p>'.
                  '<p id="redirect_option_value_' . $data['country_id'] . '_1" style="display:' . (($data['redirect_option'] == 1 || empty($data['redirect_option'])) ? 'block' : 'none') . '"><input class="small-text" name="lang_codes[]" type="text" maxlength="3" value="'.stripslashes($data['lang_code']).'"/>&nbsp;' . geo_redirect_pretty_permalink_checkbox($data) . '</p>'.
                  '<p id="redirect_option_value_' . $data['country_id'] . '_2" style="display:' . (($data['redirect_option'] == 2) ? 'block' : 'none') . '"><input class="regular-text" name="domains[]" type="text" value="'.stripslashes($data['domain']).'"/></p>'.
                  '<p id="redirect_option_value_' . $data['country_id'] . '_3" style="display:' . (($data['redirect_option'] == 3) ? 'block' : 'none') . '"><input class="regular-text" name="urls[]" type="text" value="'.stripslashes($data['url']).'"/></p>'.
                '</td>'.
                '<td>'.
                  '<p style="line-height:2.3"><a onclick="return geoRedirect.removeCountry(this);" href="#" class="delete">' . __( 'Remove' ) . '</a></p>'.
                '</td>'.
              '</tr>';
    }
  }


  $html .='<tr class="geo-redirect-option default active">'.
            '<td>'.
                '<input type="hidden" name="country_ids[]" value="' . $default_redirect['country_id'] . '"/>'.
                '<p><b>' . __( 'Default redirect' ) . '</b></p>'.
            '</td>'.
            '<td>'.
              '<p><select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">'.
                  '<option value="-1" ' . (($default_redirect['redirect_option'] == -1) ? 'selected="selected"' : '') . ' >' . __( 'None' ) . '</option>'.
                  '<option value="1" ' . (($default_redirect['redirect_option'] == 1) ? 'selected="selected"' : '') . ' >' . __( 'Language Code' ) . '</option>'.
                  '<option value="2" ' . (($default_redirect['redirect_option'] == 2) ? 'selected="selected"' : '') . ' >' . __( 'Domain Name' ) . '</option>'.
                  '<option value="3" ' . (($default_redirect['redirect_option'] == 3) ? 'selected="selected"' : '') . ' >' . __( 'Static URL' ) . '</option>'.
              '</select></p>'.
            '</td>'.
            '<td id="redirect_options_container_' . $default_redirect['country_id'] . '">'.
                //'<p><input class="small-text" name="lang_codes[]" type="text" maxlength="5" value="'.stripslashes( $default_redirect['lang_code'] ).'"/></p>'.
              '<p id="redirect_option_value_' . $default_redirect['country_id'] . '_1" style="display:' . (($default_redirect['redirect_option'] == 1) ? 'block' : 'none') . '"><input class="small-text" name="lang_codes[]" type="text" maxlength="3" value="'.stripslashes($default_redirect['lang_code']).'"/>&nbsp;' . geo_redirect_pretty_permalink_checkbox($default_redirect) . '</p>'.
                '<p id="redirect_option_value_' . $default_redirect['country_id'] . '_2" style="display:' . (($default_redirect['redirect_option'] == 2) ? 'block' : 'none') . '"><input class="regular-text" name="domains[]" type="text" value="'.stripslashes($default_redirect['domain']).'"/></p>'.
                '<p id="redirect_option_value_' . $default_redirect['country_id'] . '_3" style="display:' . (($default_redirect['redirect_option'] == 3) ? 'block' : 'none') . '"><input class="regular-text" name="urls[]" type="text" value="'.stripslashes($default_redirect['url']).'"/></p>'.
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
              <p><input class="small-text" name="lang_slug" value="'.$lang_slug.'" type="text"/>'.
              ' (example: '.get_home_url().'/?page_id=10&<b>lang</b>=en)</p>
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

  $html .= '  <p class="submit">
          <input type="submit" name="submit" class="button-primary" value="' . __( 'Save Changes' ) . '">
        </p>'; 

  $html .= wp_nonce_field( 'submit_geo_redirect_x', 'geo_redirect_nonce_y', true, false );
  $html .= '</form></div>'; 
  echo $html; 
  
  geo_redirect_javascript();
}

function geo_redirect_javascript() {
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
            j('#redirect_options_container_'+country_id).parents('.geo-redirect-option').addClass('inactive');
            setTimeout(function() {
              j('#redirect_options_container_'+country_id).parents('.geo-redirect-option').removeClass('inactive');
            }, 1000);

            j('#redirect_options_container_'+country_id).parents('.geo-redirect-option').find('select.redirect_options').focus();
          }

        });

        if (exist === true)
          return false;

        var country_name = j('select.countries option:selected').text();
        var lang_code = j('select.countries option:selected').attr('data-lang');
        var country_code = j('select.countries option:selected').attr('data-country-code');
        var option_html = '<tr class="geo-redirect-option inactive">'+
                            '<td>'+
                              '<input type="hidden" name="country_ids[]" value="'+country_id+'"/>'+
                              '<p><b>'+country_name+'</b></p>'+
                            '</td>'+
                            '<td>'+
                                '<p><select class="redirect_options" name="redirect_options[]" onchange="geoRedirect.switchOption(this);">'+
                                    '<option value="1" >Language Code</option>'+
                                    '<option value="2" >Domain Name</option>'+
                                    '<option value="3" >Static URL</option>'+
                                '</select></p>'+
                            '</td>'+
                            '<td id="redirect_options_container_'+country_id+'">'+
                              '<p id="redirect_option_value_'+country_id+'_1" style="display:block"><input class="small-text" name="lang_codes[]" type="text" maxlength="5" value="'+lang_code+'"/>&nbsp;<label title="Redirect to '+geoRedirect.home_url+'/'+lang_code+'/ instead of '+geoRedirect.home_url+'/?lang='+lang_code+'">Use pretty permalink <input type="checkbox" name="pretties[]" value="'+country_id+'"></label></p>'+
                                '<p id="redirect_option_value_'+country_id+'_2" style="display:none"><input class="regular-text" name="domains[]" type="text" value="'+geoRedirect.url_scheme+'://'+country_code+'.'+geoRedirect.domain_url+'"/></p>'+
                                '<p id="redirect_option_value_'+country_id+'_3" style="display:none"><input class="regular-text" name="urls[]" type="text" value="'+geoRedirect.home_url+'/'+country_code+'_visitors_sample_page/"/></p>'+
                            '</td>'+
                            '<td>'+
                              '<p style="line-height:2.3"><a onclick="return geoRedirect.removeCountry(this);" href="#" class="delete">' . __( 'Remove' ) . '</a></p>'+
                            '</td>'+
                          '</tr>';

        j('.geo-redirect-options table tbody').prepend(option_html);

        setTimeout(function() {
          j('.geo-redirect-option').first().addClass('active').removeClass('inactive');
        },500);

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
        j(option).parents('.geo-redirect-option').addClass('inactive');
        setTimeout(function() {
          j(option).parents('.geo-redirect-option').remove();
        },500);
        return false;
      },

      switchOption: function(select){
        var option_id = j(select).val();
        var country_id = j(select).parents('.geo-redirect-option').find('input[name="country_ids[]"]').val();
        j('#redirect_options_container_'+country_id+' p').hide();
        j('#redirect_option_value_'+country_id+'_'+option_id).show();
      }
    };
  </script> 
  <?php
}

function geo_redirect_save() {
  $country_ids      = (array) @$_POST['country_ids'];
  $redirect_options = (array) @$_POST['redirect_options'];
  $lang_codes       = (array) @$_POST['lang_codes'];
  $pretties         = (array) @$_POST['pretties'];
  $domains          = (array) @$_POST['domains'];
  $urls             = (array) @$_POST['urls'];
  $only_outsite     = intval( @$_POST['only_outsite']) ;
  $only_root        = intval( @$_POST['only_root'] );
  $only_once        = intval( @$_POST['only_once'] );
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
    'redirect'    => $redirect,
    'only_outsite'  => $only_outsite,
    'only_root'     => $only_root,
    'only_once'     => $only_once,
    'lang_slug'   => $lang_slug
  );
          
  update_option( 'geo_redirect_data', $data );
}

add_action( 'admin_menu', 'geo_redirect_admin_page' );
