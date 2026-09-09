<?php
/**
 * Dummy content file
 *
 * @package Password Protected
 */

defined( 'ABSPATH' ) || exit;

echo '<div class="disabled-content click-to-display-popup">
    <div class="pp-wrap-content"></div>
    <div class="pp-pro-branding" style="margin-top: 10px" >';

if ( isset( $k['slug'] ) ) {
	switch ( $k['slug'] ) {
		case 'exclude-from-protection':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'exclude_protection'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Exclude From Password Protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="">Exclude Pages</label></th>
                            <td>
                                <input disabled placeholder="Select pages to exclude" type="text" class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="">Exclude Posts</label></th>
                            <td>
                                <input disabled placeholder="Select posts to exclude" type="text" class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="">Exclude post Types</label></th>
                            <td>
                                <input disabled placeholder="Select post types to exclude" type="text" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>';
			break;
		case 'attempt-limitation':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'attempt_limitation'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Limit Password Attempts <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="">No of Attempts</label></th>
                            <td>
                                <input disabled placeholder="Limit Password Attempts" type="text" class="regular-text" />
                            </td>
                        </tr>
                        
                        <tr>
                            <th><label for="">Lockdown Time In Minutes:	</label></th>
                            <td>
                                <input disabled placeholder="Lockdown Time" type="text" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </div>';
			break;
		case 'bypass-url':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'bypass_url'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Bypass URL <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="">Enable Bypass URL</label>
                            </th>
                            <td>
                                <div class="pp-toggle-wrapper">
                                    <input disabled type="checkbox" >
                                    <label class="pp-toggle">
                                        <span class="pp-toggle-slider"></span>
                                    </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th>
                                <label for="">Set Bypass key</label>
                            </th>
                            <td>
                                <input disabled type="text" class="regular-text">
                            </td>
                        </tr>
                        
                        <tr>
                            <th>
                                <label for="">Redirect To</label>
                            </th>
                            <td>
                                <input disabled type="text" class="regular-text">
                            </td>
                        </tr>
                    </table>
                </div>';
			break;
		case 'manage_passwords':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'multiple_passwords'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Manage Passwords <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <button disabled class="button button-secondary">Add New Password</button>
                    <br><br>
                    
                    <table class="wp-list-table widefat fixed striped table-view-list toplevel_page_password-protected">
                        <thead>
                            <tr>
                                <th>Password</th>
                                <th>Uses Remaining</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Actions</th>
                                <th>Bypass URL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6">
                                    Manage passwords are only available in Password Protected Pro version.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Password</th>
                                <th>Uses Remaining</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Actions</th>
                                <th>Bypass URL</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>';
			break;
		case 'activity_logs':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'activity_logs'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Activity Logs <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="wp-list-table widefat fixed striped table-view-list toplevel_page_password-protected">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Browser</th>
                                <th>Status</th>
                                <th>Password</th>
                                <th>Date Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5">
                                    Activity logs are only available in Password Protected Pro version.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>IP</th>
                                <th>Browser</th>
                                <th>Status</th>
                                <th>Password</th>
                                <th>Date Time</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>';
			break;
		case 'post-type-protection':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'post_protection'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Post type protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="form-table">
                        <tr>
                            <th>Post Type</th>
                            <th>Global</th>
                            <th>Individual</th>
                        </tr>

                        <tr>
                            <th>Post</th>
                            <td><input disabled type="checkbox"></td>
                            <td><input disabled type="checkbox"></td>
                        </tr>
                        <tr>
                            <th>Page</th>
                            <td><input disabled type="checkbox"></td>
                            <td><input disabled type="checkbox"></td>
                        </tr>
                    </table>
                </div>';
			break;
		case 'taxonomy-protection':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'taxonomy_protection'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Category/Taxonomy protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>

                    <table class="form-table">
                        <tr>
                            <th>Category</th>
                            <td><input disabled type="checkbox"></td>
                        </tr>
                        <tr>
                            <th>Post_tag</th>
                            <td><input disabled type="checkbox"></td>
                        </tr>
                    </table>
                </div>';
			break;
		case 'partial-protection':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'partial_protection'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Category/Taxonomy protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>

                    <table class="form-table">
                        <tr>
                            <th>Enable</th>
                            <td>
                                <div class="pp-toggle-wrapper">
                                    <input disabled type="checkbox" >
                                    <label class="pp-toggle">
                                        <span class="pp-toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <label>Enable Partial Content Protection</label>
                                
                                <p>Enable this option to allow content protection using shortcodes.</p>
                            </td>
                        </tr>
                    </table>
                </div>';

			break;
		case 'whitelist-user-role':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'whitelist_user_role'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>White List User Roles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="form-table">
                        <tr>
                            <th>Enable Whitelist User Roles</th>
                            <td>
                                <div class="pp-toggle-wrapper">
                                    <input disabled type="checkbox" >
                                    <label class="pp-toggle">
                                        <span class="pp-toggle-slider"></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Whitelist User Roles</th>
                            <td>
                                <input disabled type="text" class="regular-text">
                            </td>
                        </tr>
                    </table>
                    <h2>WP Login Screen Redirect</h2>
                    <table class="form-table">
                        <tr>
                            <th>Enable WP Login Screen Redirection</th>
                            <td>
                                <div class="pp-toggle-wrapper">
                                        <input disabled type="checkbox" >
                                        <label class="pp-toggle">
                                            <span class="pp-toggle-slider"></span>
                                        </label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Add Text for Redirection Link</th>
                            <td><textarea disabled class="regular-text"></textarea></td>
                        </tr>
                    </table>
                </div>';
			break;
		case 'wp-admin-protection':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'wpadmin_protection'
				),
				'https://passwordprotectedwp.com/pricing/'
			);
			echo '<div>
                    <h2>Enable Admin Protection <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="form-table">
                        <tr>
                            <th>Enable</th>
                            <td><div class="pp-toggle-wrapper">
                                    <input disabled type="checkbox" >
                                    <label class="pp-toggle">
                                        <span class="pp-toggle-slider"></span>
                                    </label>
                                </div></td>
                        </tr>
                    </table>
                    <h2>Password</h2>
                    <table class="form-table">
                        <tr>
                            <th>Password</th>
                            <td>
                                <input disabled type="text" class="regular-text" />
                                <br><br>
                                <input disabled type="text" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                    <h2>Remember Me</h2>
                    <table class="form-table">
                        <tr>
                            <th>Remember Me</th>
                            <td><div class="pp-toggle-wrapper">
                                    <input disabled type="checkbox" >
                                    <label class="pp-toggle">
                                        <span class="pp-toggle-slider"></span>
                                    </label>
                                </div></td>
                        </tr>
                        <tr>
                            <th>Remember Me Many Days</th>
                            <td>
                                <input disabled type="text" class="regular-text" />
                            </td>
                        </tr>
                    </table>
                    <h2>Forgot Password</h2>
                    <table class="form-table">
                        <tr>
                            <th>Forgot Password</th>
                            <td><div class="pp-toggle-wrapper">
                                    <input disabled type="checkbox" >
                                    <label class="pp-toggle">
                                        <span class="pp-toggle-slider"></span>
                                    </label>
                                </div></td>
                        </tr>
                    </table>
                </div>';
			break;

		case 'logo-styles':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'logo_styles'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			$image_url = admin_url( 'images/wordpress-logo.svg' );

			echo '<div>
                    <h2>Logo Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>

                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="logo">logo</label></th><td><div class="pp-media-wrapper">
					<input type="hidden" value="0" id="logo" name="password_protected_logo_styles[logo]">
					<div class="pp-media-preview"><img src="' . esc_attr( $image_url ) . '" alt="' . esc_attr( $image_url ) . '"></div>
					<button class="button pp-media-upload">Upload</button>
					<button class="button pp-media-remove">Remove</button>
				</div></td></tr><tr><th scope="row"><label for="logo_width">Logo Width</label></th><td><div class="range-slider-wrapper"><label for="logo_width" pp-customizer-placeholder="px"><strong>84px</strong></label><input pp-default-value="84" id="logo_width" class="regular-text range-slider-input" name="password_protected_logo_styles[logo_width]" min="30" max="400" step="1" value="84" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="84">Reset</button></div></td></tr><tr><th scope="row"><label for="logo_height">Logo Height</label></th><td><div class="range-slider-wrapper"><label for="logo_height" pp-customizer-placeholder="px"><strong>84px</strong></label><input pp-default-value="84" id="logo_height" class="regular-text range-slider-input" name="password_protected_logo_styles[logo_height]" min="30" max="400" step="1" value="84" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="84">Reset</button></div></td></tr><tr><th scope="row"><label for="redirect_url">Redirect URL</label></th><td><select id="redirect_url" name="password_protected_logo_styles[redirect_url]" class="regular-text">
					<option value="2" selected="selected">Sample Page</option>
				</select></td></tr><tr><th scope="row"><label for="disable_logo">Disable Logo</label></th><td><div class="pp-toggle-wrapper">
					<input type="checkbox" value="yes" id="disable_logo" name="password_protected_logo_styles[disable_logo]">
					<label class="pp-toggle" for="disable_logo">
						<span class="pp-toggle-slider"></span>
					</label>
				</div></td></tr></tbody></table>
                </div>';
			break;
		case 'label-styles':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'label_styles'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Label Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="label">Label</label></th><td><input type="text" value="Password" id="label" name="password_protected_labels_styles[label]" class="regular-text"></td></tr><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_labels_styles[font]" class="regular-text">
					<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
				</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>14px</strong></label><input pp-default-value="14" id="font-size" class="regular-text range-slider-input" name="password_protected_labels_styles[font-size]" min="13" max="40" step="1" value="14" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="14">Reset</button></div></td></tr><tr><th scope="row"><label for="position">Position</label></th><td><div class="range-slider-wrapper"><label for="position" pp-customizer-placeholder="px"><strong>2px</strong></label><input pp-default-value="2" id="position" class="regular-text range-slider-input" name="password_protected_labels_styles[position]" min="0" max="20" step="1" value="2" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="2">Reset</button></div></td></tr><tr><th scope="row"><label for="color">Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(114, 119, 124);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#72777c" id="color" name="password_protected_labels_styles[color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 92.8838px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 235, 235), rgb(255, 245, 235), rgb(255, 255, 235), rgb(245, 255, 235), rgb(235, 255, 235), rgb(235, 255, 245), rgb(235, 255, 255), rgb(235, 245, 255), rgb(235, 235, 255), rgb(245, 235, 255), rgb(255, 235, 255), rgb(255, 235, 245), rgb(255, 235, 235));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 61, 122), rgb(125, 125, 125));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 8%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>
				
                </div>';
			break;
		case 'field-styles':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'field_styles'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Field Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(251, 251, 251);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#fbfbfb" id="bg-color" name="password_protected_fields_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 3.6425px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(250, 0, 0), rgb(250, 250, 250));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border">Border</label></th><td><div class="range-slider-wrapper"><label for="border" pp-customizer-placeholder="px"><strong>1px</strong></label><input pp-default-value="1" id="border" class="regular-text range-slider-input" name="password_protected_fields_styles[border]" min="0" max="10" step="1" value="1" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="1">Reset</button></div></td></tr><tr><th scope="row"><label for="border-color">Border Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(221, 221, 221);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#dddddd" id="border-color" name="password_protected_fields_styles[border-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 23.6762px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(219, 0, 0), rgb(222, 222, 222));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="margin-bottom">Margin Bottom</label></th><td><div class="range-slider-wrapper"><label for="margin-bottom" pp-customizer-placeholder="px"><strong>16px</strong></label><input pp-default-value="16" id="margin-bottom" class="regular-text range-slider-input" name="password_protected_fields_styles[margin-bottom]" min="1" max="60" step="1" value="16" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="16">Reset</button></div></td></tr><tr><th scope="row"><label for="padding">Padding</label></th><td><div class="range-slider-wrapper"><label for="padding" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="padding" class="regular-text range-slider-input" name="password_protected_fields_styles[padding]" min="0" max="40" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-top">Padding Top</label></th><td><div class="range-slider-wrapper"><label for="padding-top" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="padding-top" class="regular-text range-slider-input" name="password_protected_fields_styles[padding-top]" min="0" max="40" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-bottom">Padding Bottom</label></th><td><div class="range-slider-wrapper"><label for="padding-bottom" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="padding-bottom" class="regular-text range-slider-input" name="password_protected_fields_styles[padding-bottom]" min="0" max="40" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border-radius" class="regular-text range-slider-input" name="password_protected_fields_styles[border-radius]" min="0" max="60" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow">Shadow</label></th><td><div class="range-slider-wrapper"><label for="shadow" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="shadow" class="regular-text range-slider-input" name="password_protected_fields_styles[shadow]" min="0" max="30" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-opacity">Shadow Opacity</label></th><td><div class="range-slider-wrapper"><label for="shadow-opacity" pp-customizer-placeholder="%"><strong>7%</strong></label><input pp-default-value="7" id="shadow-opacity" class="regular-text range-slider-input" name="password_protected_fields_styles[shadow-opacity]" min="0" max="100" step="1" value="7" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="7">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-inset">Shadow Inset</label></th><td><div class="pp-toggle-wrapper">
					<input type="checkbox" value="yes" id="shadow-inset" name="password_protected_fields_styles[shadow-inset]">
					<label class="pp-toggle" for="shadow-inset">
						<span class="pp-toggle-slider"></span>
					</label>
				</div></td></tr></tbody></table>
				
				<h2>Text Styles</h2>
				
				<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_fields_styles[font]" class="regular-text">
					<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
				</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>24px</strong></label><input pp-default-value="24" id="font-size" class="regular-text range-slider-input" name="password_protected_fields_styles[font-size]" min="13" max="40" step="1" value="24" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="24">Reset</button></div></td></tr><tr><th scope="row"><label for="text-color">Text Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(130, 36, 227);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#8224e3" id="text-color" name="password_protected_fields_styles[text-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 136.594px; top: 20.0337px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 41, 41), rgb(255, 148, 41), rgb(255, 255, 41), rgb(148, 255, 41), rgb(41, 255, 41), rgb(41, 255, 148), rgb(41, 255, 255), rgb(41, 148, 255), rgb(41, 41, 255), rgb(148, 41, 255), rgb(255, 41, 255), rgb(255, 41, 148), rgb(255, 41, 41));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(112, 0, 224), rgb(227, 227, 227));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 84%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>
                </div>';
			break;
		case 'button-styles':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'button_styles'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Button Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(0, 133, 186);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#0085ba" id="bg-color" name="password_protected_button_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 99.6628px; top: 49.1737px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 0, 0), rgb(255, 128, 0), rgb(255, 255, 0), rgb(128, 255, 0), rgb(0, 255, 0), rgb(0, 255, 128), rgb(0, 255, 255), rgb(0, 128, 255), rgb(0, 0, 255), rgb(128, 0, 255), rgb(255, 0, 255), rgb(255, 0, 128), rgb(255, 0, 0));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 132, 184), rgb(186, 186, 186));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 100%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border">Border</label></th><td><div class="range-slider-wrapper"><label for="border" pp-customizer-placeholder="px"><strong>1px</strong></label><input pp-default-value="1" id="border" class="regular-text range-slider-input" name="password_protected_button_styles[border]" min="0" max="10" step="1" value="1" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="1">Reset</button></div></td></tr><tr><th scope="row"><label for="border-color">Border Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(0, 115, 170);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#0073aa" id="border-color" name="password_protected_button_styles[border-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 100.675px; top: 60.1012px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 0, 0), rgb(255, 128, 0), rgb(255, 255, 0), rgb(128, 255, 0), rgb(0, 255, 0), rgb(0, 255, 128), rgb(0, 255, 255), rgb(0, 128, 255), rgb(0, 0, 255), rgb(128, 0, 255), rgb(255, 0, 255), rgb(255, 0, 128), rgb(255, 0, 0));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 115, 168), rgb(171, 171, 171));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 100%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="padding">Padding</label></th><td><div class="range-slider-wrapper"><label for="padding" pp-customizer-placeholder="px"><strong>12px</strong></label><input pp-default-value="12" id="padding" class="regular-text range-slider-input" name="password_protected_button_styles[padding]" min="0" max="60" step="1" value="12" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="12">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-top">Padding Top</label></th><td><div class="range-slider-wrapper"><label for="padding-top" pp-customizer-placeholder="px"><strong>4px</strong></label><input pp-default-value="4" id="padding-top" class="regular-text range-slider-input" name="password_protected_button_styles[padding-top]" min="1" max="20" step="1" value="4" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="4">Reset</button></div></td></tr><tr><th scope="row"><label for="padding-bottom">Padding Bottom</label></th><td><div class="range-slider-wrapper"><label for="padding-bottom" pp-customizer-placeholder="px"><strong>4px</strong></label><input pp-default-value="4" id="padding-bottom" class="regular-text range-slider-input" name="password_protected_button_styles[padding-bottom]" min="1" max="20" step="1" value="4" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="4">Reset</button></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="border-radius" class="regular-text range-slider-input" name="password_protected_button_styles[border-radius]" min="0" max="60" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow">Shadow</label></th><td><div class="range-slider-wrapper"><label for="shadow" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="shadow" class="regular-text range-slider-input" name="password_protected_button_styles[shadow]" min="0" max="30" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-opacity">Shadow Opacity</label></th><td><div class="range-slider-wrapper"><label for="shadow-opacity" pp-customizer-placeholder="%"><strong>0%</strong></label><input pp-default-value="0" id="shadow-opacity" class="regular-text range-slider-input" name="password_protected_button_styles[shadow-opacity]" min="0" max="100" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr></tbody></table>
                    <h2>Text Styles</h2>
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_button_styles[font]" class="regular-text">
					<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
				</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>13px</strong></label><input pp-default-value="13" id="font-size" class="regular-text range-slider-input" name="password_protected_button_styles[font-size]" min="13" max="40" step="1" value="13" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="13">Reset</button></div></td></tr><tr><th scope="row"><label for="text-color">Text Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(5, 5, 5);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#050505" id="text-color" name="password_protected_button_styles[text-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 178.482px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(5, 0, 0), rgb(5, 5, 5));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>

                </div>';
			break;
		case 'remember-me-styles':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'remember_me_styles'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Checkbox Styles <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="size">Size</label></th><td><div class="range-slider-wrapper"><label for="size" pp-customizer-placeholder="px"><strong>16px</strong></label><input pp-default-value="16" id="size" class="regular-text range-slider-input" name="password_protected_rememberme_styles[size]" min="16" max="20" step="1" value="16" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="16">Reset</button></div></td></tr><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(251, 251, 251);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#fbfbfb" id="bg-color" name="password_protected_rememberme_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 3.6425px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(250, 0, 0), rgb(250, 250, 250));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border">Border</label></th><td><div class="range-slider-wrapper"><label for="border" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border" class="regular-text range-slider-input" name="password_protected_rememberme_styles[border]" min="0" max="3" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="border-color">Border Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(180, 185, 190);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#b4b9be" id="border-color" name="password_protected_rememberme_styles[border-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 45.5312px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 240, 240), rgb(255, 247, 240), rgb(255, 255, 240), rgb(247, 255, 240), rgb(240, 255, 240), rgb(240, 255, 247), rgb(240, 255, 255), rgb(240, 247, 255), rgb(240, 240, 255), rgb(247, 240, 255), rgb(255, 240, 255), rgb(255, 240, 247), rgb(255, 240, 240));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 94, 189), rgb(191, 191, 191));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 5%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border-radius" class="regular-text range-slider-input" name="password_protected_rememberme_styles[border-radius]" min="0" max="30" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr></tbody></table>
                    
                    <h2>Label Styles</h2>
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_rememberme_styles[font]" class="regular-text">
					<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
				</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>12px</strong></label><input pp-default-value="12" id="font-size" class="regular-text range-slider-input" name="password_protected_rememberme_styles[font-size]" min="8" max="20" step="1" value="12" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="12">Reset</button></div></td></tr><tr><th scope="row"><label for="position">Position</label></th><td><div class="range-slider-wrapper"><label for="position" pp-customizer-placeholder="px"><strong>5px</strong></label><input pp-default-value="5" id="position" class="regular-text range-slider-input" name="password_protected_rememberme_styles[position]" min="0" max="20" step="1" value="5" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="5">Reset</button></div></td></tr><tr><th scope="row"><label for="color">Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(114, 119, 124);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#72777c" id="color" name="password_protected_rememberme_styles[color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 92.8838px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 235, 235), rgb(255, 245, 235), rgb(255, 255, 235), rgb(245, 255, 235), rgb(235, 255, 235), rgb(235, 255, 245), rgb(235, 255, 255), rgb(235, 245, 255), rgb(235, 235, 255), rgb(245, 235, 255), rgb(255, 235, 255), rgb(255, 235, 245), rgb(255, 235, 235));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 61, 122), rgb(125, 125, 125));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 8%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr></tbody></table>

                </div>';
			break;
		case 'form-background':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'form_background'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Form Background <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>

                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(255, 255, 255);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#ffffff" id="bg-color" name="password_protected_form_bg_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 0px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(255, 0, 0), rgb(255, 255, 255));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="border-radius">Border Radius</label></th><td><div class="range-slider-wrapper"><label for="border-radius" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="border-radius" class="regular-text range-slider-input" name="password_protected_form_bg_styles[border-radius]" min="0" max="50" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow">Shadow</label></th><td><div class="range-slider-wrapper"><label for="shadow" pp-customizer-placeholder="px"><strong>3px</strong></label><input pp-default-value="3" id="shadow" class="regular-text range-slider-input" name="password_protected_form_bg_styles[shadow]" min="0" max="70" step="1" value="3" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="3">Reset</button></div></td></tr><tr><th scope="row"><label for="shadow-opacity">Shadow Opacity</label></th><td><div class="range-slider-wrapper"><label for="shadow-opacity" pp-customizer-placeholder="%"><strong>13%</strong></label><input pp-default-value="13" id="shadow-opacity" class="regular-text range-slider-input" name="password_protected_form_bg_styles[shadow-opacity]" min="0" max="100" step="1" value="13" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="13">Reset</button></div></td></tr><tr><th scope="row"><label for="side-padding">Side Padding</label></th><td><div class="range-slider-wrapper"><label for="side-padding" pp-customizer-placeholder="px"><strong>24px</strong></label><input pp-default-value="24" id="side-padding" class="regular-text range-slider-input" name="password_protected_form_bg_styles[side-padding]" min="0" max="100" step="1" value="24" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="24">Reset</button></div></td></tr><tr><th scope="row"><label for="transparent">Transparent</label></th><td><div class="pp-toggle-wrapper">
					<input type="checkbox" value="yes" id="transparent" name="password_protected_form_bg_styles[transparent]">
					<label class="pp-toggle" for="transparent">
						<span class="pp-toggle-slider"></span>
					</label>
				</div></td></tr><tr><th scope="row"><label for="vertical-padding">Vertical Padding</label></th><td><div class="range-slider-wrapper"><label for="vertical-padding" pp-customizer-placeholder="px"><strong>26px</strong></label><input pp-default-value="26" id="vertical-padding" class="regular-text range-slider-input" name="password_protected_form_bg_styles[vertical-padding]" min="0" max="100" step="1" value="26" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="26">Reset</button></div></td></tr><tr><th scope="row"><label for="width">Width</label></th><td><div class="range-slider-wrapper"><label for="width" pp-customizer-placeholder="px"><strong>320px</strong></label><input pp-default-value="320" id="width" class="regular-text range-slider-input" name="password_protected_form_bg_styles[width]" min="300" max="800" step="1" value="320" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="320">Reset</button></div></td></tr></tbody></table>

                </div>';
			break;
		case 'body-background':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'body_background'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Body Background <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="bg-color">Background Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(241, 241, 241);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#f1f1f1" id="bg-color" name="password_protected_body_bg_styles[bg-color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 0px; top: 9.10625px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255), rgb(255, 255, 255));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(240, 0, 0), rgb(242, 242, 242));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 0%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="bg-image">Background Image</label></th><td><div class="pp-media-wrapper">
					<input type="hidden" value="5" id="bg-image" name="password_protected_body_bg_styles[bg-image]">
					<div class="pp-media-preview"></div>
					<button class="button pp-media-upload">Upload</button>
					<button class="button pp-media-remove">Remove</button>
				</div></td></tr><tr><th scope="row"><label for="bg-repeat">Background Repeat</label></th><td><select id="bg-repeat" name="password_protected_body_bg_styles[bg-repeat]" class="regular-text">
					<option value="no-repeat" selected="selected">No Repeat</option><option value="repeat">Repeat</option><option value="repeat-x">Repeat Horizontally</option><option value="repeat-y">Repeat Vertically</option>
				</select></td></tr><tr><th scope="row"><label for="bg-size">Background Size</label></th><td><select id="bg-size" name="password_protected_body_bg_styles[bg-size]" class="regular-text">
					<option value="auto">Auto</option><option value="cover" selected="selected">Cover</option><option value="contain">Contain</option>
				</select></td></tr><tr><th scope="row"><label for="bg-position">Background Position</label></th><td><select id="bg-position" name="password_protected_body_bg_styles[bg-position]" class="regular-text">
					<option value="left top">Left Top</option><option value="left center">Left Center</option><option value="left bottom">Left Bottom</option><option value="right top">Right Top</option><option value="right center">Right Center</option><option value="right bottom">Right Bottom</option><option value="center top">Center Top</option><option value="center center" selected="selected">Center Center</option><option value="center bottom">Center Bottom</option>
				</select></td></tr></tbody></table>
                    
                </div>';
			break;
		case 'below-form':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'form_content'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Form Content <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="font">Font</label></th><td><select id="font" name="password_protected_below_form_styles[font]" class="regular-text">
					<option value="default" selected="selected">Default</option><option value="Abril Fatface">Abril Fatface</option><option value="Georgia">Georgia</option><option value="Helvetica">Helvetica</option><option value="Lato">Lato</option><option value="Lora">Lora</option><option value="Karla">Karla</option><option value="Josefin Sans">Josefin Sans</option><option value="Montserrat">Montserrat</option><option value="Open Sans">Open Sans</option><option value="Oswald">Oswald</option><option value="Overpass">Overpass</option><option value="Poppins">Poppins</option><option value="PT Sans">PT Sans</option><option value="Roboto">Roboto</option><option value="Fira Sans">Fira Sans</option><option value="Times New Roman">Times New Roman</option><option value="Nunito">Nunito</option><option value="Merriweather">Merriweather</option><option value="Rubik">Rubik</option><option value="Playfair Display">Playfair Display</option><option value="Spectral">Spectral</option>
				</select></td></tr><tr><th scope="row"><label for="font-size">Font Size</label></th><td><div class="range-slider-wrapper"><label for="font-size" pp-customizer-placeholder="px"><strong>14px</strong></label><input pp-default-value="14" id="font-size" class="regular-text range-slider-input" name="password_protected_below_form_styles[font-size]" min="0" max="100" step="1" value="14" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="14">Reset</button></div></td></tr><tr><th scope="row"><label for="position">Position</label></th><td><div class="range-slider-wrapper"><label for="position" pp-customizer-placeholder="px"><strong>0px</strong></label><input pp-default-value="0" id="position" class="regular-text range-slider-input" name="password_protected_below_form_styles[position]" min="0" max="100" step="1" value="0" type="range"><button type="button" style="margin: 5px 0 0 5px" class="button button-secondary button-small reset-range" pp-default-value="0">Reset</button></div></td></tr><tr><th scope="row"><label for="color">Color</label></th><td><div class="wp-picker-container"><button type="button" class="button wp-color-result" aria-expanded="false" style="background-color: rgb(114, 119, 124);"><span class="wp-color-result-text">Select Color</span></button><span class="wp-picker-input-wrap hidden"><label><span class="screen-reader-text">Color value</span><input type="text" value="#72777c" id="color" name="password_protected_below_form_styles[color]" class="regular-text pp-color-selector wp-color-picker"></label><input type="button" class="button button-small wp-picker-clear" value="Clear" aria-label="Clear color"></span><div class="wp-picker-holder"><div class="iris-picker iris-border" style="display: none; width: 255px; height: 202.125px; padding-bottom: 23.2209px;"><div class="iris-picker-inner"><div class="iris-square" style="width: 182.125px; height: 182.125px;"><a class="iris-square-value ui-draggable ui-draggable-handle" href="#" style="left: 106.24px; top: 92.8838px;"><span class="iris-square-handle ui-slider-handle"></span></a><div class="iris-square-inner iris-square-horiz" style="background-image: -webkit-linear-gradient(left, rgb(255, 235, 235), rgb(255, 245, 235), rgb(255, 255, 235), rgb(245, 255, 235), rgb(235, 255, 235), rgb(235, 255, 245), rgb(235, 255, 255), rgb(235, 245, 255), rgb(235, 235, 255), rgb(245, 235, 255), rgb(255, 235, 255), rgb(255, 235, 245), rgb(255, 235, 235));"></div><div class="iris-square-inner iris-square-vert" style="background-image: -webkit-linear-gradient(top, rgba(0, 0, 0, 0), rgb(0, 0, 0));"></div></div><div class="iris-slider iris-strip" style="height: 205.346px; width: 28.2px; background-image: -webkit-linear-gradient(top, rgb(0, 61, 122), rgb(125, 125, 125));"><div class="iris-slider-offset ui-slider ui-corner-all ui-slider-vertical ui-widget ui-widget-content"><span tabindex="0" class="ui-slider-handle ui-corner-all ui-state-default" style="bottom: 8%;"></span></div></div></div><div class="iris-palette-container"><a class="iris-palette" tabindex="0" style="background-color: rgb(0, 0, 0); height: 19.5784px; width: 19.5784px; margin-left: 0px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(255, 255, 255); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 51, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(221, 153, 51); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(238, 238, 34); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(129, 215, 66); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(30, 115, 190); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a><a class="iris-palette" tabindex="0" style="background-color: rgb(130, 36, 227); height: 19.5784px; width: 19.5784px; margin-left: 3.6425px;"></a></div></div></div></div></td></tr><tr><th scope="row"><label for="text-alignment">Text Alignment</label></th><td><select id="text-alignment" name="password_protected_below_form_styles[text-alignment]" class="regular-text">
					<option value="left">Left</option><option value="center" selected="selected">Center</option><option value="right">Right</option>
				</select></td></tr></tbody></table>
                    
                </div>';
			break;
		case 'custom-css':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'custom_css'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Custom CSS <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="custom-css">Custom CSS</label></th><td><textarea id="custom-css" name="password_protected_custom_css_styles[custom-css]" class="large-text"></textarea></td></tr></tbody></table>
                    
                </div>';
			break;

		case 'password-request':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'password-request'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Request Password <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="enable-password-requests">Enable Password Requests</label></th><td><div class="pp-toggle-wrapper">
					<input type="checkbox" value="yes" id="enable-password-requests" name="pp_password_request_setting[enable-password-requests]" checked="checked">
					<label class="pp-toggle" for="enable-password-requests">
						<span class="pp-toggle-slider"></span>
					</label>
				</div><p class="desc"><strong>Enable password requests for your site.</strong></p></td></tr><tr><th scope="row"><label for="password-request-label">Password Request Label</label></th><td><input type="text" value="Request for password" id="password-request-label" name="pp_password_request_setting[password-request-label]" class="regular-text"><p class="desc"><strong>Change the label of the password request button.</strong></p></td></tr><tr><th scope="row"><label for="add-your-email-label">Add Your Email Label</label></th><td><input type="text" value="Email Address" id="add-your-email-label" name="pp_password_request_setting[add-your-email-label]" class="regular-text"><p class="desc"><strong>Change the label of the email input field.</strong></p></td></tr><tr><th scope="row"><label for="email-place-holder-label">Email Placeholder Label</label></th><td><input type="text" value="Enter your email address" id="email-place-holder-label" name="pp_password_request_setting[email-place-holder-label]" class="regular-text"><p class="desc"><strong>Change the placeholder of the email input field.</strong></p></td></tr><tr><th scope="row"><label for="validation-button-label">Validation Button Label</label></th><td><input type="text" value="Validate your email" id="validation-button-label" name="pp_password_request_setting[validation-button-label]" class="regular-text"><p class="desc"><strong>Change the label of the validation button.</strong></p></td></tr></tbody></table>
                </div>';
			break;
		case 'requests':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'password-request'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Requests <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <div class="pp-settings-wrapper">
								<div style="margin: 10px 0;">
						<a style="margin: 0 10px;text-decoration: none;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests">All (1)</a>
						<a style="margin: 0 10px;text-decoration: none;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;status=pending">Pending (0)</a>
						<a style="margin: 0 10px;text-decoration: none;;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;status=approved">Approved (1)</a>
						<a style="margin: 0 10px;text-decoration: none;;" href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;status=rejected">Rejected (0)</a>
					</div><table class="wp-list-table widefat fixed striped table-view-list ">
			<thead>
	<tr>
		<th scope="col" id="id" class="manage-column column-id column-primary">ID</th><th scope="col" id="email" class="manage-column column-email">Email</th><th scope="col" id="requested_content" class="manage-column column-requested_content">Requested Content</th><th scope="col" id="status" class="manage-column column-status">Status</th><th scope="col" id="datetime" class="manage-column column-datetime">Date &amp; Time</th><th scope="col" id="action" class="manage-column column-action">Action</th>	</tr>
	</thead>

	<tbody id="the-list">
		<tr><td class="id column-id has-row-actions column-primary" data-colname="ID">1<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button></td><td class="email column-email" data-colname="Email">email@localwp.com</td><td class="requested_content column-requested_content" data-colname="Requested Content">wp-admin/admin.sds</td><td class="status column-status" data-colname="Status"><span class="pp-status pp-status-approved">Approved</span></td><td class="datetime column-datetime" data-colname="Date &amp; Time">March 27, 2025, 6:06 am</td><td class="action column-action" data-colname="Action"><a href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;action=resend&amp;id=1&amp;_wpnonce=b5c8a49b1b&amp;email=email@localwp.com" class="pp-rensend-password-email">Send Password Email</a>
				|
				<a href="http://password-protected.test/wp-admin/admin.php?page=password-protected&amp;tab=request-password&amp;sub-tab=requests&amp;action=delete&amp;id=1&amp;_wpnonce=5468d5c8c5" class="pp-delete-request">Delete</a></td></tr>	</tbody>

	<tfoot>
	<tr>
		<th scope="col" class="manage-column column-id column-primary">ID</th><th scope="col" class="manage-column column-email">Email</th><th scope="col" class="manage-column column-requested_content">Requested Content</th><th scope="col" class="manage-column column-status">Status</th><th scope="col" class="manage-column column-datetime">Date &amp; Time</th><th scope="col" class="manage-column column-action">Action</th>	</tr>
	</tfoot>

</table>
		                            </div>
                </div>';
			break;
		case 'email-templates':
			$url = add_query_arg(
				array(
					'utm_source'   => 'plugin',
					'utm_medium'   => 'pop_up',
					'utm_campaign' => 'plugin',
					'utm_content'  => 'password-request'
				),
				'https://passwordprotectedwp.com/pricing/'
			);

			echo '<div>
                    <h2>Email Templates <span class="pro-badge"><a href="' . $url . '">PRO</a></span></h2>
                    
                    <div class="ppp-email-templates" id="validations"><h2>Validation\'s</h2>
<table class="form-table" role="presentation"><tbody><tr><th scope="row"><label for="rp-validation-subject">Email Subject</label></th><td><input type="text" value="Verify Your Email Address for Password Request" id="rp-validation-subject" name="pp_email_templates_setting[rp-validation-subject]" class="regular-text"><p class="desc"><strong>Email template for validation email subject. Use <code>{site_name}</code> to replace with site name.</strong></p></td></tr><tr><th scope="row"><label for="rp-validation-body">Email Body</label></th><td><textarea id="rp-validation-body" name="pp_email_templates_setting[rp-validation-body]" class="regular-text">Hello,
Thank you for requesting access to our protected content. To proceed, we need to verify your email address.
                                                                                                   Please click the link below to validate your email:
{validation_link}
If you did not make this request, you can ignore this email.
                                                      Best regards,
{site_name}</textarea><p class="desc"><strong>Email template for validation email body. Use <code>{site_name}</code> to replace with site name. use <code>{validation_link}</code> to replace with validation link.</strong></p></td></tr></tbody></table></div>
                </div>';
			break;
	}
}

echo '</div>
</div>';
