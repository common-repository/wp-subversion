<?php
/*
Plugin Name: WP-Subversion
Description: Automatically post SVN log messages to WordPress.
Author: Dan Coulter
Version: 0.1.1
Author URI: http://dancoulter.com/
*/

class dtc_WPsvn {
	function install_event() {
		wp_schedule_event(time(), 'wp_svn', 'check_svn');
	}

	function load_svn() {
		$options = get_option('wp-svn');
		if ( !isset($options['repository']) ) {
			return 1;
		}
		if ( !isset($options['revision']) ) {
			$options['revision'] = 0;
			exec('svn log --limit 10 ' . $options['repository'], $result);
		} else {
			exec('svn log --revision ' . ($options['revision'] + 1) . ':HEAD ' . $options['repository'], $result);
		}
		$revisions = array();
		foreach ( $result as $line ) {
			if ( '------------------------------------------------------------------------' == $line ) {
				if ( is_array($tmp) ) {
					$tmp['content'] = trim($tmp['content']);
					$revisions[] = $tmp;
				}
				$tmp = array();
				continue;
			}
			if ( empty( $tmp ) ) {
				$tmp['info'] = explode(' | ', $line);
				$tmp['raw'] = $line;
				$tmp['revision'] = substr($tmp['info'][0], 1);
				$tmp['by'] = $tmp['info'][1];
				$date = explode(' ', trim(preg_replace('~\(.*\)~', '', $tmp['info'][2])));
				$date = str_replace('-', '', $date[0]) . 'T' . $date[1] . $date[2];
				
				$tmp['gmt'] = iso8601_to_datetime($date, GMT);
				$tmp['local'] = get_date_from_gmt($tmp['gmt']);
				$tmp['lines'] = (int) $tmp['info'][3];
			} else {
				if ( trim($line) == '' ) {
					$tmp['content'] .= "\n\n";
				} else {
					$tmp['content'] .= trim($line);
				}
			}
		}
		
		print_r($revisions);
		foreach ( $revisions as $r ) {
			$post_title = strip_tags( $r['content'] );
			if( strlen( $post_title ) > 40 ) {
				$post_title = substr( $post_title, 0, 40 ) . ' ... ';
			}
			
			$author = array_search($r['by'], $options['users']);
			if ( $author === false ) $author = 1;

			$id = wp_insert_post(array(
				'post_title' => $post_title,
				'post_content' => $r['content'],
				'post_status' => 'publish',
				'post_author' => $author,
				'post_date' => $r['local'],
				'post_modified' => $r['local'],
				'post_date_gmt' => $r['gmt'],
				'post_modified_gmt' => $r['gmt'],
			));

			wp_set_post_tags($id, array(
				$r['by'],
				$options['project_name']
			));
			$options['revision'] = ( $r['revision'] > $options['revision'] ) ? $r['revision'] : $options['revision'];
			update_option('wp-svn', $options, '', 'no');
		}
	}
	
	function wp_svn_schedule($schedules) {
		$schedules['wp_svn'] = array( 'interval' => 300, 'display' => __('WP-Subversion', 'wpsvn') );
		return $schedules;
	}
	
	
	function add_options_page () {
		add_options_page(
			__('WP-Subversion', 'wpsvn'),         //Title
			__('WP-Subversion', 'wpsvn'),         //Sub-menu title
			'manage_options', //Security
			__FILE__,         //File to open
			array('dtc_WPsvn', 'options_page')  //Function to call
		);  
	}
	
	function options_page(){
		if ( $_GET['bladerunner'] == 'check' ) {
			dtc_WPsvn::load_svn();
		}

		$options = get_option('wp-svn');
		if ( $options === false ) {
			$options = array();
		}
		if ( isset($_POST['repository']) ) {
			$options = array_merge($options, $_POST);
			update_option('wp-svn', $options);
		}
		$users = get_users_of_blog();
		?>
			<div class="wrap">
				<h2><?php _e('WP-Subversion', 'wpsvn'); ?></h2>
				<form method="post">
					<table class="form-table">
						<tr>
							<td scope="row" valign="top"><label for="repository"><?php _e('Repository URL:', 'wpsvn'); ?></label> </td>
							<td>
								<input type="text" style="width: 300px" id="repository" name="repository" value="<?php if ( isset($options['repository']) ) echo $options['repository']; ?>" />
							</td>
						</tr>
						<tr>
							<td scope="row" valign="top"><label for="project_name"><?php _e('Project Name:', 'wpsvn'); ?></label> </td>
							<td>
								<input type="text" style="width: 300px" id="project_name" name="project_name" value="<?php if ( isset($options['project_name']) ) echo $options['project_name']; ?>" />
							</td>
						</tr>
						<tr>
							<td colspan="2" scope="row" valign="top"><?php _e('Map the users of your blog to committers.  Any committers you don&rsquo;t map will get assigned to "admin".', 'wpsvn'); ?></td>
						</tr>
						<?php foreach ( $users as $u ) : ?>
							<tr>
								<td style="text-align: right"><?php echo $u->display_name; ?>: </td>
								<td>
									<input type="text" value="<?php if ( isset($options['users'][$u->user_id]) ) echo $options['users'][$u->user_id]; ?> " name="users[<?php echo $u->user_id; ?>]" />
								</td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<td scope="row" valign="top"></td>
							<td>
								<input type="submit" class="button" value="<?php _e('Save Options', 'wpsvn'); ?>" />
							</td>
						</tr>
					</table>
				</form>
			</div>
		<?php
	}

}

add_filter('cron_schedules', array('dtc_WPsvn', 'wp_svn_schedule'));
register_activation_hook(__FILE__, array('dtc_WPsvn', 'install_event'));
add_action('check_svn', array('dtc_WPsvn', 'load_svn'));
add_action('admin_menu', array('dtc_WPsvn', 'add_options_page'));
	
?>
