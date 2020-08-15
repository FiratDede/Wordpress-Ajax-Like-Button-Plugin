<?php
/*
    * Plugin name: Ajax Like Button Plugin
    *Description: Adds a like button to each post. Also shows 10 most liked posts in a widget in descending order you can change how many post shown.Also shows a tag list which shows how many likes a tag get and count of a tag in descending order.
    * Author: Fırat Dede
    *License:     GPL2

*/

register_activation_hook( __FILE__, "fd_ajax_like_button_activated" );

function fd_ajax_like_button_activated(){
    
    global $wpdb;
    $check=$wpdb->query( $wpdb->prepare("Create table {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 
     ( Tag_ID bigint(20) NOT NULL,count bigint(20),like_amount bigint(20)  ,PRIMARY KEY (Tag_ID), INDEX (count,like_amount)
      ); 
      
      "));
    $all_tags=get_tags([ 'hide_empty' => false]);
      
    foreach($all_tags as $tag){
        $wpdb->query($wpdb->prepare("Insert into {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 (Tag_ID,count,like_amount) 
        values(%d,%d,%d) ",[$tag->term_id,$tag->count,0]));
    }
   
    $all_posts=get_posts( ["post_type"=>"post",'numberposts'=>-1,"post_status"=>"publish" ] );
    foreach($all_posts as $post)
    add_post_meta( $post->ID, "fd_ajax_like_button_like_amount", 0, true);
}

add_filter("the_content","fd_add_like_buttons",10,1);
function fd_add_like_buttons($content){  
    
    if(!is_page()&&!post_password_required(  )){
        if(is_user_logged_in()){
        if(!in_array(get_the_ID(),get_user_meta( get_current_user_id(),"fd_like_button",false),false))
        $content.="<div> <i id='".get_the_ID()."' style='color:black;'  class='fa fa-thumbs-up fd_like' aria-hidden='true'></i></i></div>";
        else
        $content.="<div> <i id='".get_the_ID()."' style='color:green;'  class='fa fa-thumbs-up fd_like' aria-hidden='true'></i></i></div>";
        }
        else {
            $content.="<div> <i id='".get_the_ID()."' style='color:black;'  class='fa fa-thumbs-up fd_visitor_like' aria-hidden='true'></i></i></div>";
        }
    }
    return $content;
}

add_action( 'wp_insert_post', "fd_ajax_like_button_new_post_added", 10,3 );
function fd_ajax_like_button_new_post_added($post_ID,  $post,  $update ){
    if($post->post_type=="post"&&!wp_is_post_revision( $post_id )){
        add_post_meta( $post_ID, "fd_ajax_like_button_like_amount", 0, true);
    }

}


add_action("deleted_post","fd_ajax_like_button_delete_post_from_database",10,1);
function fd_ajax_like_button_delete_post_from_database($postid){
    global $wpdb;
    delete_post_meta(  $postid, "fd_ajax_like_button_like_amount");
    $wpdb->query("Delete from wp_usermeta where meta_key='fd_like_button' and meta_value=".$postid);
    
}

add_action("wp_head","fd_add_font_awesome");
function fd_add_font_awesome(){
    ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">
    
     

<?php
}
add_action("wp_enqueue_scripts","fd_enqueque_my_script");

function fd_enqueque_my_script(){
    wp_enqueue_script("fd_ajax_like_button", plugins_url( '/js/ajax_like_button.js', __FILE__ ),
    array('jquery'));
    $check_data_nonce = wp_create_nonce( 'fd_like_button_check_data' );
wp_localize_script( "fd_ajax_like_button", 'fd_like_button_ajax_object', array(
   'ajax_url' => admin_url( 'admin-ajax.php' ),
   'nonce'    => $check_data_nonce,
) );
}
add_action("wp_ajax_fd_like_button_action","fd_get_like_request");
function fd_get_like_request(){
    global $wpdb;
    check_ajax_referer('fd_like_button_check_data' );
    
    if($_POST["fd_my_data1"]=="black"){      
        add_user_meta( get_current_user_id(), "fd_like_button", intval($_POST["fd_my_data2"]),false );
        update_post_meta( intval($_POST["fd_my_data2"]),"fd_ajax_like_button_like_amount",
        get_post_meta( intval($_POST["fd_my_data2"]), "fd_ajax_like_button_like_amount",true )+1 );
       $all_tag_ids= get_tags( ['object_ids'=>intval($_POST["fd_my_data2"]),'fields'=>"ids"] );
       foreach($all_tag_ids as $tag_id){
        $wpdb->query($wpdb->prepare("Update {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 
        set like_amount=like_amount+1 where Tag_ID=%d ",$tag_id));
       }

        echo "green";
       
    }
    else{ 
        delete_user_meta( get_current_user_id(), "fd_like_button", intval($_POST["fd_my_data2"]) );
        update_post_meta( intval($_POST["fd_my_data2"]),"fd_ajax_like_button_like_amount",
        get_post_meta( intval($_POST["fd_my_data2"]), "fd_ajax_like_button_like_amount",true )-1 );
        $all_tag_ids= get_tags( ['object_ids'=>intval($_POST["fd_my_data2"]),'fields'=>"ids"] );
       foreach($all_tag_ids as $tag_id){
        $wpdb->query($wpdb->prepare("Update {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 
        set like_amount=like_amount-1 where Tag_ID=%d ",$tag_id));
       }
        echo "black";
    }
    wp_die();

}
register_deactivation_hook( __FILE__    ,  "fd_ajax_like_button_deactivated" );
function fd_ajax_like_button_deactivated(){
    global $wpdb;
    delete_post_meta_by_key( "fd_ajax_like_button_like_amount" );
    $all_users=get_users(["meta_key"=>"fd_like_button"]);
    foreach($all_users as $user)
    delete_user_meta( $user->ID, "fd_like_button");
    $wpdb->query($wpdb->prepare("Drop table {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 "));


}

class Post_Like_Widget extends WP_Widget 
{
    function __construct()
    {
        parent::__construct("fd_ajax_like_button_post_like_widget","Post Like Widget",
        array("description"=>"This widget shows ten most liked posts with their like amounts"));
    }

    
  
    public function widget( $args, $instance ) 
    {   $number_of_posts=10;
        $title="Post Likes";
        echo $args["before_widget"];
        echo $args["before_title"].$title.$args["after_title"];
        if(isset($instance["fd_show_the_number_of_posts"])){
            $number_of_posts=intval($instance["fd_show_the_number_of_posts"]);}

            $posts=get_posts(array("post_type"=>"post","meta_key"=>"fd_ajax_like_button_like_amount","orderby"=>"fd_ajax_like_button_like_amount"
            ,"order"=>"desc","numberposts"=>$number_of_posts));
      
            foreach($posts as $post){
                if(post_password_required( $post->ID )) continue;
                echo "Post title= ".$post->post_title."  likes= ".get_post_meta( $post->ID,"fd_ajax_like_button_like_amount", true)."<br>";
            

        }


        echo $args["after_widget"];


    }
    public function form($instance){
        $current_number_of_posts=10;
        if(isset($instance['fd_show_the_number_of_posts'])){
            $current_number_of_posts=intval($instance['fd_show_the_number_of_posts']);
        }
        ?>
         <label>How many most liked posts do you want to see in descending order? </label><br>
        <select    name="<?php echo $this->get_field_name("fd_show_the_number_of_posts"); ?>"  
        id="<?php echo $this->get_field_id( 'fd_show_the_number_of_posts' ); ?>"  >
        <?php
        for($i=10; $i>0; --$i){
         ?>
        <option value='<?php echo $i; ?>'  <?php if( $current_number_of_posts==$i) echo "selected"; ?>  ><?php echo $i; ?></option>
        <?php  } ?>
        </select>
        <?php  
        
        
        
    }
   
    public function update($new_instance,$old_instance){
        return $new_instance;
    }


}

add_action( 'widgets_init', 'fd_ajax_like_button_wpb_load_widget' );
function fd_ajax_like_button_wpb_load_widget() {
    register_widget( 'Post_Like_Widget' );
}

add_action( "added_term_relationship", "fd_ajax_like_button_added_term_updation", 10,3);
function fd_ajax_like_button_added_term_updation($object_id,  $tt_id,  $taxonomy){
    global $wpdb;
    if($taxonomy=="post_tag"){
        $plus_like_amount=intval(get_post_meta( $object_id, "fd_ajax_like_button_like_amount", true ));
        $wpdb->query($wpdb->prepare("Update {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 set count=count+1, 
        like_amount=like_amount+{$plus_like_amount} where Tag_ID=%d ",$tt_id));
        

        
    }


}
add_action( "deleted_term_relationships", "fd_ajax_like_button_deleteded_term_updation", 10,3 ); 
function fd_ajax_like_button_deleteded_term_updation($object_id,  $tt_ids,  $taxonomy){
    global $wpdb;
    if($taxonomy=="post_tag"&&get_post_status($object_id)!="trash"){
        $minus_like_amount=intval(get_post_meta( $object_id, "fd_ajax_like_button_like_amount", true ));
        foreach($tt_ids as $tt_id){
        $wpdb->query($wpdb->prepare("Update {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 set count=count-1, 
        like_amount=like_amount-{$minus_like_amount} where Tag_ID=%d ",$tt_id));
        }
      
    }
}
add_action( 'created_term', "fd_ajax_like_button_update_for_new_term",10,3 );
function fd_ajax_like_button_update_for_new_term( $term_id,  $tt_id,  $taxonomy){
    global $wpdb;
    if($taxonomy=="post_tag"){
       
        $wpdb->query($wpdb->prepare("Insert into {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 (Tag_ID,count,like_amount) 
        values(%d,%d,%d) ",[$term_id,0,0]));

    }
}
add_action( 'delete_term', "fd_ajax_like_button_update_for_deleted_term",10,5 );
function fd_ajax_like_button_update_for_deleted_term( $term_id,  $tt_id,  $taxonomy,  $deleted_term,  $object_ids ){
    global $wpdb;
    if($taxonomy=="post_tag"){
        $wpdb->query($wpdb->prepare("Delete from  {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 where Tag_ID=%d",$term_id));
    }

}
add_action( "trashed_post", "fd_ajax_like_button_update_for_trash_post", 10 , 1 );
function fd_ajax_like_button_update_for_trash_post($post_id ){
    global $wpdb;
    if(!wp_is_post_revision($post_id)&&!is_page($post_id)){
        $idstagsofpost=get_tags( [ 'object_ids'=>$post_id ,'fields'=>"ids"]);
        $minus_like_amount=intval(get_post_meta( $post_id, "fd_ajax_like_button_like_amount", true));
        foreach($idstagsofpost as $idtagofpost){
            $wpdb->query($wpdb->prepare("Update {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 set count=count-1, 
        like_amount=like_amount-{$minus_like_amount} where Tag_ID=%d ",$idtagofpost));
        }


    }
}
add_action( "untrashed_post", "fd_ajax_like_button_update_for_untrash_post", 10, 1 );

function fd_ajax_like_button_update_for_untrash_post($post_id){
    global $wpdb;
    if(!wp_is_post_revision($post_id)&&!is_page($post_id)){
        $idstagsofpost=get_tags( [ 'object_ids'=>$post_id ,'fields'=>"ids"]);
        $plus_like_amount=intval(get_post_meta( $post_id, "fd_ajax_like_button_like_amount", true));
        foreach($idstagsofpost as $idtagofpost){
            $wpdb->query($wpdb->prepare("Update {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547 set count=count+1, 
        like_amount=like_amount+{$plus_like_amount} where Tag_ID=%d ",$idtagofpost));
        }

    }
}

add_shortcode( "fd_ajax_like_button_show_tags_with_likes", "__fd_ajax_like_button_show_tags_with_likes");

function __fd_ajax_like_button_show_tags_with_likes(){

    global $wpdb,$post;
    $currentpage=1;
    $all_tags=0;
    if(isset($_GET["tagpage"])){
        $all_tags=$wpdb->get_results("Select * from  {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547  
        ORDER BY  like_amount DESC,`count` DESC LIMIT ".strval((intval($_GET["tagpage"])-1)*10).",10");
        $currentpage=intval($_GET["tagpage"]);

    }
    else{
        $all_tags=$wpdb->get_results("Select * from  {$wpdb->prefix}fd_ajax_like_button_my_tag_list12547841547  
        ORDER BY  like_amount DESC,`count` DESC LIMIT 0,10");
        
    }
   
    $totalpage=ceil(count( get_tags(["hide_empty"=>false]))/10);
    $beginning=$currentpage*10-9;
   ?>
    <style>
    #fd_next1{
        
        float: right;
    }
   
    </style>
        <div>
            <table>
                <tr>
                <th>Number </th>
                <th>Tag Name</th>
                <th>Like Amount</th>
                <th>Post Count</th>
                </tr>
        <?php
        foreach($all_tags as $tag){
            echo "<tr> <td> ".$beginning.") "."</td> <td>".get_term($tag->Tag_ID)->name."</td>".
            "<td>".$tag->like_amount."</td>".
            "<td>".$tag->count."</td> </tr>";
                ++$beginning;
            }
        
            ?>
             </table> 
         <div ><span><a  id="prev1" href="<?php  echo get_permalink( ); ?>/?tagpage=<?php echo $currentpage-1; ?>" 
         <?php if($currentpage==1) echo "hidden";  ?> >Previous </a></span>   
        <span id="fd_next1"><a <?php if($currentpage==$totalpage) echo "hidden"; ?> href="<?php  echo get_permalink( ); ?>/?tagpage=<?php echo $currentpage+1; ?>"  >
        Next</a> </span>
        
        </div>  
        </div>


<?php

}



