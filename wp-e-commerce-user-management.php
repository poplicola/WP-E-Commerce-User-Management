<?php
/*
Plugin Name: WP E-Commerce User Management
Description: This allows the Wordpress administrator to edit shop user data
Version: 1.0
Author: Poplicola
Author URI: http://poplicola.com
*/

/*  Copyright 2010 Jay Margalus  (email : hello@jaymargalus.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Add Plugin to Administrative Menu */
add_action('admin_menu', 'wpecomgmt');
function wpecomgmt() {
	add_users_page('E-Commerce User Management', 'E-Commerce User Management', 'edit_users', 'wpecomgmt', 'wpecom_user_mgmt');
}
/* End Administrative Menu */

function wpecom_user_mgmt() {

	if (!current_user_can('edit_users'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	global $wpdb;
	
	echo "<p style='padding:20px 0 10px;'>";
	
	echo "<a href='admin.php?page=wpecomgmt&thepage=registered' style='float:left;'>";
	echo "Registered User Data</a>";
	echo "<a href='admin.php?page=wpecomgmt&thepage=unregistered' style='float:left;margin:0 0 0 20px;'>";
	echo "Unregistered User Data</a>";
	
	echo "</p>";
	
	echo"<hr style='width:100%;clear:both;' />";
	
	$thepage = $_GET["thepage"];
	$userinfo = $_GET["userinfo"];
	
	$runform = $_POST["runform"];
	$formids=$_POST["formids"];
	$log_id=$_POST["userinfo"];
	$formids = $wpdb->get_results( "SELECT id,name,type FROM wp_wpsc_checkout_forms" );
	if ($runform==1) {
		foreach ($formids as $theids) {
			$id=$theids->id;
			$updateddata=$_POST[$id];
			$wpdb->query("UPDATE wp_wpsc_submited_form_data SET value='".$updateddata."' WHERE form_id=".$id." AND log_id=".$log_id);
		}
	}
	
	if ($thepage=="registered") {
		/* If $userinfo hasn't filled with requested information, list all registered users */
		if ($userinfo<1) {
			echo '<div class="wrap">';

			/* Checks how many registered users exist */
			$allUIDs=$wpdb->get_results( "SELECT user_id FROM wp_usermeta" );
			$allUIDs=max($allUIDs);
			$usercount=$allUIDs->user_id;
			$counter=1;
			/* End Check */

			while ($counter<=$usercount) {
				$user_ID=$counter; 
				$meta_data = get_usermeta($user_ID, 'wpshpcrt_usr_profile');
				if (isset($meta_data[2]) || isset($meta_data[3]) ) {
					echo "<p style='padding:0 0 10px;'><a href='admin.php?page=wpecomgmt&thepage=registered&userinfo=" . $user_ID . "'>";
					echo $meta_data[2] . " " . $meta_data[3];
					echo "</a></p>";
				}
				$counter++;
			}
		} else {
			/* This displays individual user data */
			$user_ID=$userinfo;
			$any_bad_inputs = false;
			$changes_saved = false;
			$_SESSION['collected_data'] = null;
			if($_POST['collected_data'] != null) {
			  foreach((array)$_POST['collected_data'] as $value_id => $value) {
			    $form_sql = "SELECT * FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `id` = '$value_id' LIMIT 1";
			    $form_data = $wpdb->get_results($form_sql,ARRAY_A);
			    $form_data = $form_data[0];
			    $bad_input = false;
			    if($form_data['mandatory'] == 1) {
			      switch($form_data['type']) {
			        case "email":
			        if(!preg_match("/^[a-zA-Z0-9._-]+@[a-zA-Z0-9-.]+\.[a-zA-Z]{2,5}$/",$value)) {
			          $any_bad_inputs = true;
			          $bad_input = true;
							}
			        break;

			        case "delivery_country":
			        if(($value != null)) {
			          $_SESSION['delivery_country'] == $value;
							}
			        break;

			        default:
			        break;
			        }
			      if($bad_input === true) {
			        switch($form_data['name']) {
			          case __('First Name', 'wpsc'):
			          $bad_input_message .= __('Please enter a valid name', 'wpsc') . "";
			          break;

			          case __('Last Name', 'wpsc'):
			          $bad_input_message .= __('Please enter a valid surname', 'wpsc') . "";
			          break;

			          case __('Email', 'wpsc'):
			          $bad_input_message .= __('Please enter a valid email address', 'wpsc') . "";
			          break;

			          case __('Address 1', 'wpsc'):
			          case __('Address 2', 'wpsc'):
			          $bad_input_message .= __('Please enter a valid address', 'wpsc') . "";
			          break;

			          case __('City', 'wpsc'):
			          $bad_input_message .= __('Please enter your town or city.', 'wpsc') . "";
			          break;

			          case __('Phone', 'wpsc'):
			          $bad_input_message .= __('Please enter a valid phone number', 'wpsc') . "";
			          break;

			          case __('Country', 'wpsc'):
			          $bad_input_message .= __('Please select your country from the list.', 'wpsc') . "";
			          break;

			          default:
			          $bad_input_message .= __('Please enter a valid', 'wpsc') . " " . strtolower($form_data['name']) . ".";
			          break;
							}
			        $bad_input_message .= "<br />";
						} else {
							$meta_data[$value_id] = $value;
						}
					} else {
						$meta_data[$value_id] = $value;
					}
				}

			  $new_meta_data = serialize($meta_data);
			  update_usermeta($user_ID, 'wpshpcrt_usr_profile', $meta_data);
			} 
			?>
			<div class="wrap" style=''>
			<form method='post' action=''>
			<?php
			if($changes_saved == true) {
			  echo __('Thanks, your changes have been saved.', 'wpsc');
			} else {
			  echo $bad_input_message;
			}
			?>
			<table>
			<?php
			// arr, this here be where the data will be saved
			$meta_data = null;
			$saved_data_sql = "SELECT * FROM `".$wpdb->usermeta."` WHERE `user_id` = '".$user_ID."' AND `meta_key` = 'wpshpcrt_usr_profile';";
			$saved_data = $wpdb->get_row($saved_data_sql,ARRAY_A);

			$meta_data = get_usermeta($user_ID, 'wpshpcrt_usr_profile');

			$form_sql = "SELECT * FROM `".WPSC_TABLE_CHECKOUT_FORMS."` WHERE `active` = '1' ORDER BY `order`;";
			$form_data = $wpdb->get_results($form_sql,ARRAY_A);

			foreach($form_data as $form_field)
			  {
			  $meta_data[$form_field['id']] = htmlentities(stripslashes($meta_data[$form_field['id']]), ENT_QUOTES);
			  if($form_field['type'] == 'heading')
			    {
			    }
			    else
			      {
			      if($form_field['type'] == "country")
			        {
			        continue;
			        }

			      echo "
			      <tr>
			        <td align='left'>\n\r";
			      echo $form_field['name'];
			      if($form_field['mandatory'] == 1)
			        {
			        if(!(($form_field['type'] == 'country') || ($form_field['type'] == 'delivery_country')))
			          {
			          echo "*";
			          }
			        }
			      echo "
			        </td>\n\r
			        <td  align='left'>\n\r";
			      switch($form_field['type'])
			        {
			        case "text":
			        case "city":
			        case "delivery_city":
			        echo "<input type='text' value='".$meta_data[$form_field['id']]."' name='collected_data[".$form_field['id']."]' />";
			        break;

			        case "address":
			        case "delivery_address":
			        case "textarea":
			        echo "<textarea name='collected_data[".$form_field['id']."]'>".$meta_data[$form_field['id']]."</textarea>";
			        break;


			        case "region":
			        case "delivery_region":
			        echo "<select name='collected_data[".$form_field['id']."]'>".nzshpcrt_region_list($_SESSION['collected_data'][$form_field['id']])."</select>";
			        break;


			        case "country":   
			        break;

			        case "delivery_country":
			        echo "<select name='collected_data[".$form_field['id']."]' >".nzshpcrt_country_list($meta_data[$form_field['id']])."</select>";
			        break;

			        case "email":
			        echo "<input type='text' value='".$meta_data[$form_field['id']]."' name='collected_data[".$form_field['id']."]' />";
			        break;

			        default:
			        echo "<input type='text' value='".$meta_data[$form_field['id']]."' name='collected_data[".$form_field['id']."]' />";
			        break;
			        }
			      echo "
			        </td>
			      </tr>\n\r";
			      }
			  }
			  ?>
			    <?php
			    if(isset($gateway_checkout_form_fields))
			      {
			      echo $gateway_checkout_form_fields;
			      }
			    ?>
			    <tr>
			      <td>
			      </td>
			      <td>
			      <input type='hidden' value='true' name='submitwpcheckout_profile' />
			      <input type='submit' value='<?php echo __('Save Profile', 'wpsc');?>' name='submit' />
			      </td>
			    </tr>
			</table>
			</form>
			</div><?php
		}
	} elseif ($thepage=="unregistered") {
		if ($userinfo<1) {

			/* Checks how many unregistered users exist */
			$allUIDs=$wpdb->get_results( "SELECT log_id FROM wp_wpsc_submited_form_data" );
			$allUIDs=max($allUIDs);
			$usercount=$allUIDs->log_id;
			$counter=1;
			/* End Check */

			while ($counter<=$usercount) {
				$user_ID=$counter; 
				$firstname = $wpdb->get_results( "SELECT value FROM wp_wpsc_submited_form_data WHERE log_id=" . $user_ID . " AND form_id=2" );
				$lastname = $wpdb->get_results( "SELECT value FROM wp_wpsc_submited_form_data WHERE log_id=" . $user_ID . " AND form_id=3" );
				$firstname = $firstname[0]->value;
				$lastname = $lastname[0]->value;
				if (isset($firstname) || isset($lastname) ) {
					echo "<p style='padding:0 0 10px;'><a href='admin.php?page=wpecomgmt&thepage=unregistered&userinfo=" . $user_ID . "'>";
				
					echo $firstname . " " . $lastname;
					echo "</a></p>";
				}
				$counter++;
			}
		} else {
			$user_ID = $userinfo;
			$formids = $wpdb->get_results( "SELECT id,name,type FROM wp_wpsc_checkout_forms" );
			echo "<div style='width:350px;'><form method='post' action=''>";
				foreach ($formids as $theids) {
					$id=$theids->id;
					$name=$theids->name;
					$type=$theids->type;
					$formdata = $wpdb->get_results( "SELECT value FROM wp_wpsc_submited_form_data WHERE log_id=" . $userinfo . " AND form_id=" . $id );
					$formdata = $formdata[0]->value;
					switch ($type) {
						case "heading";
						echo "<span style='float:left;clear:both;margin:10px 0 10px;'>" . $name . "</span>";
						break;
					}
					switch ($name) {
						case "First Name";
						case "Last Name";
						case "City";
						case "Zip Code";
						case "Email";
						echo "<span style='float:left;clear:both;margin:0 0 10px;'>" . $name . "</span>";
						echo "<span style='float:right;'><input type='text' name='" . $id . "' value='" . $formdata . "' onblur=\"if (this.value == '') {this.value = '" . $formdata . "'; this.style.color = '#000000';}\" onfocus=\"if (this.value == '" . $formdata . "') {this.value = ''; this.style.color = '#000000'; }\"></input></span>";
					break;
						
					default:
						if ($type!="heading" && $name!="Country" && $name!="State") {
							echo "<span style='float:left;clear:both;'>" . $name . "</span>";
							echo "<span style='float:right;'><input type='text' name='" . $id . "' value='" . $formdata . "' onblur=\"if (this.value == '') {this.value = '" . $formdata . "'; this.style.color = '#000000';}\" onfocus=\"if (this.value == '" . $formdata . "') {this.value = ''; this.style.color = '#000000'; }\"></input></span>";
						}
					break;
					}
				}
			echo "<input type='hidden' value='1' name='runform' />";
			echo "<input type='hidden' value='" . $formids . "' name='formids' />";
			echo "<input type='hidden' value='" . $userinfo . "' name='userinfo' />";
			echo "<input type='submit' value='Submit' name='submit' style='clear:both;float:left;margin:15px 0 0;' />";
			echo "</form></div>";
		}
	}
}

?>