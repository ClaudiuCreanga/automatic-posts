<?php 
    /*
    Plugin Name: Automatic posts 
    Plugin URI: github
    Description: Plugin for updating posts from various sites
    Author: Claudiu Creanga
    Version: 1.0
    Author URI: github
    */
?>

<?php
	
require_once(plugin_dir_path( __FILE__ ) . 'simple_html_dom.php');

class Scrap_Mladiinfo
{
	
	private $link;
	
	public function instantiateDom($url)
	{
	    return file_get_html($url);
	}
	
	public function getLinksToArticles()
	{
		$html = $this->instantiateDom($this->link);

		$links = $html->find('div[class="sub-trainings"] ul li a');
		
		$list_links = array();
		foreach($links as $link){
			$list_links[] = $link->href;
		}
		
		return $list_links;
	}
	
	public function getIndividualArticles()
	{
		
		$links = $this->getLinksToArticles();
		
		$articles = array();
		foreach($links as $key => $link)
		{
			$article = array();
			$html = $this->instantiateDom($link);
			
			$title = $html->find('article[id*="post-"] h1', 0)->innertext;
			
			// remove unwanted parts
		    $this->removeElement($html,'article[id*="post-"] div[class="adsense"]');
		    $this->removeElement($html,'script');
		    $this->removeElement($html,'a[class="__cf_email__"]');
				
		    $content = $html->find('article[id*="post-"] section', 0)->innertext;
		    
		    $tags = array();
		    foreach($html->find('a[rel="tag"]') as $key => $tag){
			    $tags[] = $tag->innertext;
		    }
		    $article["title"] = $title;
		    $article["content"] = $content;
		    $article["tags"] = $tags;
		    $articles[] = $article;			
		}
		
		return $articles;
		
	}
	
	public function removeElement($html,$elements)
	{
		foreach($html->find($elements) as $element){
		    $element->outertext = '';
	    }
	    return $html;
	}
	
	public function converWordpress()
	{

		$data = $this->getIndividualArticles();

		$posts = array();
		foreach($data as $item)
		{	
			$new_post = array(
				'post_title' => $item["title"],
				'post_content' => $item["content"],
				'post_status' => 'publish',
				'post_date' => date('Y-m-d H:i:s'),
				'post_author' => 2,
				'tags_input' => array(implode(",", $item["tags"])),
				'post_type' => 'post',
				'post_category' => array(869,10)
			);
			$posts[] = $new_post;
		}
		return $posts;
		
	}
	
	
	public function wp_exist_post_by_title($title)
	{
	    global $wpdb;
	    $query = $wpdb->prepare(
	        'SELECT ID FROM ' . $wpdb->posts . '
	        WHERE post_title = %s',
	        $title
	    );
	    
	    $return = $wpdb->query($query);
	    
		if($return === 0){
	        return true;
	    }else{
	        return false;
	    }
	}
	
	public function createPosts()
	{
		$data = $this->converWordpress();
		foreach($data as $item)
		{
			if($this->wp_exist_post_by_title($item["post_title"])){
				try{
	 				$the_post_id = wp_insert_post($item);
	 			} catch (Exception $e) {
				    echo $e->getMessage();
				}
			}
		}
	}
}

// create a scheduled event (if it does not exist already)
function cronstarter_activation() {
	if( !wp_next_scheduled( 'mycronjob' ) ) {  
	   wp_schedule_event( time(), 'daily', 'mycronjob' );  
	}
}
// and make sure it's called whenever WordPress loads
add_action('wp', 'cronstarter_activation');
add_action ('mycronjob', 'my_repeat_function'); 
// here's the function we'd like to call with our cron job
function my_repeat_function() {
	


}
$object = new Scrap_Mladiinfo();
$object->createPosts();


?>