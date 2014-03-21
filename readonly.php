<?php
/*
Plugin Name: WP Scheduled Read-Only
Description: Schedule readonly mode for WordPress
Version: 0.1.0
Author: bastho
Domain Path: /languages/
Network: 1
*/

if(is_multisite()) 
	$WPScheduledReadOnly=new WPScheduledReadOnly();

class WPScheduledReadOnly{
	public $active;
	public $now;
	public $from;
	public $to;
	function WPScheduledReadOnly(){
		load_plugin_textdomain( 'wp_scheduled_readonly', false, 'wp-scheduled-readonly/languages' );		
		add_action( 'network_admin_menu', array(&$this,'network_menu'));		
		add_action( 'admin_init', array(&$this,'admin_init'));	
		
		add_action('admin_post_wp_scheduled_readonly', array(&$this,'save_conf'));
		
		add_filter( 'comments_template', array(&$this,'comments_template'),100,1);		
		add_action( 'wp_head', array(&$this,'wp_head'));	
		add_action( 'wp_loaded', array( &$this, 'filters' ) );
		
		
		
		$eelv_readonly = get_site_option( 'eelv_readonly' );
		$this->active=(isset($eelv_readonly['active']) && $eelv_readonly['active']==1)?true:false;
		$this->from=$eelv_readonly['from']!=''?strtotime($eelv_readonly['from']):'';
		$this->to=$eelv_readonly['to']!=''?strtotime($eelv_readonly['to']):'';
		$offset=get_option('gmt_offset');
		if($offset>=0)$offset='+'.$offset;
		$this->now=strtotime($offset.'hours');
	}
	// Ajout du menu d'option sur le reseau
	function network_menu() {
	  add_submenu_page('settings.php', __('Read Only', 'wp_scheduled_readonly' ), __('Read Only', 'wp_scheduled_readonly' ), 'Super Admin', 'eelv_readonly_network_configuration', array(&$this,'network_configuration'));   
	}
	function is_readonly(){
		if(!is_super_admin()){
			if($this->active==true &&
					($this->from=='' || $this->from<$this->now) &&
					($this->to=='' || $this->to>$this->now)				
				){
					return true;				
			}
		}
		return false;
	}
	function admin_init(){
		if($this->is_readonly()){
			$format=get_option('date_format').', '.get_option('time_format');
			wp_die(nl2br(sprintf(__("We are %s,\nsites are on read-only mode from %s to %s.", 'wp_scheduled_readonly' ),date_i18n($format,$this->now),date_i18n($format,$this->from),date_i18n($format,$this->to))));
		}
	}
	function wp_head(){
		if($this->is_readonly() && (is_single() || is_page())){
			global $post;
			$post->comment_status=false;
		}
	}
	function comments_template($val){
		if($this->is_readonly()){
			//return dirname( __FILE__ ) . '/comments-template.php';
		}
		return $val;
	}
	function comment_status($open, $post_id){
		if($this->is_readonly()){
			return false;
		}
		return $open;
	}
	function filters(){
		if(!is_admin()){
			add_filter( 'comments_open', array( $this, 'comment_status' ), 20, 2 );
			wp_deregister_script( 'comment-reply' );
		}
	}
	function save_conf(){
		if( is_super_admin() ) {    
	      update_site_option( 'eelv_readonly', $_REQUEST['eelv_readonly'] );	       
	    }
		wp_redirect('network/settings.php?page=eelv_readonly_network_configuration&ok');
	}
	function network_configuration(){
	  ?>  
	        <div class="wrap">
	        <div id="icon-edit" class="icon32 icon32-posts-newsletter"><br/></div>
	        <h2><?=_e('Scheduled Read only', 'wp_scheduled_readonly' )?></h2>
	        
	    <form method="post" action="../admin-post.php">  
	    <input type="hidden" name="action" value="wp_scheduled_readonly">
	    
	        
	        <table class="widefat" style="margin-top: 1em;">
	            <tbody>
	                <tr>
	                    <th width="20%">
	                        <label><?=_e('Read-only:', 'wp_scheduled_readonly' )?></label>
	                    </th><td>
	                        <input type="checkbox" name="eelv_readonly[active]"  value="1" <?=($this->active==true?'checked':'')?>>
	                   </td>
	                 </tr>	                 
	                 <tr>
	                    <th width="20%">
	                        <label><?=_e('From:', 'wp_scheduled_readonly' )?></label>
	                    </th><td>
	                        <input  type="datetime-local" name="eelv_readonly[from]"  size="60"  value="<?=date('Y-m-d H:i:s',$this->from)?>">
	                   </td>
	                 </tr>	                 
	                 <tr>
	                    <th width="20%">
	                        <label><?=_e('To:', 'wp_scheduled_readonly' )?></label>
	                    </th><td>
	                        <input type="datetime-local" name="eelv_readonly[to]"  size="60"  value="<?=date('Y-m-d H:i:s',$this->to)?>">
	                   </td>
	                 </tr>              
	                 
                     
	                 <tr>
	                    <td colspan="2">
	                        <p class="submit">
	                        <input type="submit"  class="button button-primary" value="<?php _e('save', 'wp_scheduled_readonly' ) ?>" />
	                        </p>                    
	                    </td>
	                </tr>
	            </tbody>
	        </table>
	        
	    </form>
	    </div>
	    
	<?php
	}
}
