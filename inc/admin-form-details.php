<?php

if (!defined( 'ABSPATH')) exit;

/**
*
*/
class CFdb7_Form_Details
{
    private $form_id;
    private $form_post_id;


    public function __construct()
    {
       $this->form_post_id = esc_sql( $_GET['fid'] );
       $this->form_id = esc_sql( $_GET['ufid'] );
       $this->table_name    = 'wp_db7_forms';
       $this->form_details_page();
    }

    public function form_details_page(){
        global $wpdb;
        $cfdb          = apply_filters( 'cfdb7_database', $wpdb );
        $table_name    = 'wp_db7_forms';
        $upload_dir    = wp_upload_dir();
        $cfdb7_dir_url = $upload_dir['baseurl'].'/equalspace_uploads';

        if ( is_numeric($this->form_post_id) && is_numeric($this->form_id) ) {

           $results    = $cfdb->get_results( "SELECT * FROM $table_name WHERE form_post_id = $this->form_post_id AND form_id = $this->form_id LIMIT 1", OBJECT );
        }

        if ( empty($results) ) {
            wp_die( $message = 'Not valid contact form' );
        }
        ?>
        <div class="wrap">
            <div id="welcome-panel" class="welcome-panel">
                <div class="welcome-panel-content">
                    <div class="welcome-panel-column-container">
                         <?php 
                           
                        
        if (@$_GET['fid'] == 5){
          do_action('cfdb7_before_formdetails_title',$this->form_post_id ); ?>
          
            <h3><?php echo get_the_title( $this->form_post_id ); ?> - Entry to be Approved</h3>

 <?php do_action('cfdb7_after_formdetails_title', $this->form_post_id ); ?>
                        <p></span><?php echo $results[0]->form_date; ?></p>
                        <?php 
                        $form_data  = unserialize( $results[0]->form_value );
                        $this->tap_form_data($form_data);
                       //var_dump($form_data);
                        ?>
<style>
    .new-entry-list th{
        text-align: right;
        padding-right: 5px;
    }
</style>

                        <table class="new-entry-list">
                            <tr>
                                <th>Game Name:</th>
                                <td><?=$form_data['game_name']?></td>
                            </tr>
                             <tr>
                                <th>Game Summary:</th>
                                <td><?=$form_data['game_summary']?></td>
                            </tr>
                           <tr>
                                <th>Game Description:</th>
                                <td><?=$form_data['game_desc']?></td>
                            </tr>
                              <?php if(isset($form_data['video_url'])){?>
                            <tr>
                                <th>Video URL</th>
                                <td>
                                    <a href="<?=$form_data['video_url']?>" target="_blank"><?=$form_data['video_url']?></a>

                                </td>
                            </tr>
                            <?php } 
                                $team_data = $this->get_team($form_data); //returns array of challengers

                            ?>
                            
                            <tr>
                                <th>Team</th>
                                <td>
                                    <?php

                                    $form_data['team_data'] = $team_data;
                                    $form_data['challengers'] = $team_data['challengers'];
                                    //var_dump($team_data);?>
                                <?=$challengers?>

                                



                                </td></tr>
                              
                           <?php if($form_data['uploadfilecfdb7_file'] != ''){?>
                            <tr>
                                <th>Attachment</th>
                                <td>
                                    <a href="<?=$cfdb7_dir_url?>/<?=$form_data['uploadfilecfdb7_file']?>" target="_blank"><?=$form_data['uploadfilecfdb7_file']?></a>

                                </td>
                            </tr>
                            <?php } ?>

                           <?php if($form_data['uploadthumbnailcfdb7_file'] != ''){?>
                            <tr>
                                <th>Thumbnail</th>
                                <td>
                                    <img src="<?=$cfdb7_dir_url?>/<?=$form_data['uploadthumbnailcfdb7_file']?>" style="max-width:400px;">

                                </td>
                            </tr>
                            <?php } ?>



                            </td>
                            </tr>
                            <tr>
                                <th></th>
                                <td></td>
                            </tr>
                            
                        </table>

                        <a href="admin.php?page=cfdb7-list.php&fid=<?=@$_GET['fid']?>&ufid=<?=@$_GET['ufid']?>&approved=1">Approve</a>
                <?php 
                  
                      if(@$_GET['approved']){

                        print $this->tap_form_data($form_data);
                   
                        $form_data['cfdb7_status'] = 'read';
                        $form_data = serialize( $form_data );
                        $form_id = $results[0]->form_id;

                        $cfdb->query( "UPDATE $this->table_name SET form_value =
                            '$form_data' WHERE form_id = $form_id"
                        );

                      }   
                        ?>



            <?php
        } else {




                      


                        do_action('cfdb7_before_formdetails_title',$this->form_post_id ); ?>
                        <h3><?php echo get_the_title( $this->form_post_id ); ?></h3>
                        <?php do_action('cfdb7_after_formdetails_title', $this->form_post_id ); ?>
                        <p></span><?php echo $results[0]->form_date; ?></p>
                        <?php $form_data  = unserialize( $results[0]->form_value );
                        $this->tap_form_data($form_data);

                        foreach ($form_data as $key => $data):

                            if ( $key == 'cfdb7_status' )  continue;

                            if ( strpos($key, 'cfdb7_file') !== false ){

                                $key_val = str_replace('cfdb7_file', '', $key);
                                $key_val = str_replace('your-', '', $key_val);
                                $key_val = ucfirst( $key_val );
                                echo '<p><b>'.$key_val.'</b>: <a href="'.$cfdb7_dir_url.'/'.$data.'">'
                                .$data.'</a></p>';
                            }else{


                                if ( is_array($data) ) {

                                    $key_val = str_replace('your-', '', $key);
                                    $key_val = ucfirst( $key_val );
                                    $arr_str_data =  implode(', ',$data);
                                    echo '<p><b>'.$key_val.'</b>: '. nl2br($arr_str_data) .'</p>';

                                }else{

                                    $key_val = str_replace('your-', '', $key);
                                    $key_val = ucfirst( $key_val );
                                    echo '<p><b>'.$key_val.'</b>: '.nl2br($data).'</p>';
                                }
                            }

                        endforeach;
                    
                      

                        $form_data['cfdb7_status'] = 'read';
                        $form_data = serialize( $form_data );
                        $form_id = $results[0]->form_id;

                        $cfdb->query( "UPDATE $table_name SET form_value =
                            '$form_data' WHERE form_id = $form_id"
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
        do_action('cfdb7_after_formdetails', $this->form_post_id );
    }

   


    //#equalspace challenge 
    static public function slugify($text)
        {
          // replace non letter or digits by -
          $text = preg_replace('~[^\pL\d]+~u', '-', $text);

          // transliterate
          $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

          // remove unwanted characters
          $text = preg_replace('~[^-\w]+~', '', $text);

          // trim
          $text = trim($text, '-');

          // remove duplicate -
          $text = preg_replace('~-+~', '-', $text);

          // lowercase
          $text = strtolower($text);

          if (empty($text)) {
            return 'n-a';
          }

          return $text;
        }

   public function age_flag($team_member){
        if(isset($team_member[3])){
            $team_member[0] = trim($team_member[0])."*";
        }
        return $team_member;
    }
   
    public function get_team($form_data){
        extract($form_data);
        $team = array();
        $team_data = array();
        $challenger_list = array();

        if($team_member_0 != ''){

            array_push($team,explode("|",
                $team_member_0));//splits array on ""|"  array is full name|email|phone|under18
                $team[0] = $this->age_flag($team[0]);

            array_push($challenger_list,$team[0][0]);//

        }
        if($team_member_1 != ''){
            array_push($team,explode("|",$team_member_1));
            $team[1] = $this->age_flag($team[1]);
            array_push($challenger_list,$team[1][0]);//
        }
        if($team_member_2 != ''){
            array_push($team,explode("|",$team_member_2));
             $team[2] = $this->age_flag($team[2]);
            array_push($challenger_list,$team[2][0]);//
        }
        if($team_member_3 != ''){
            array_push($team,explode("|",$team_member_3));
             $team[3] = $this->age_flag($team[3]);
            array_push($challenger_list,$team[3][0]);//
        }
        if($team_member_4 != ''){
            array_push($team,explode("|",$team_member_4));
             $team[4] = $this->age_flag($team[4]);
            array_push($challenger_list,$team[4][0]);//
        }
        $team_data['team'] = $team;
      
        $challengers = "";
        if(count($challenger_list) == 1){
            $challengers .= implode("", $challenger_list[0]);
        } else if(count($challenger_list) == 2){
            $challengers .= implode("", $challenger_list[0]." & ". $challenger_list[1] );
        } else if(count($challenger_list) > 2){
            
            foreach($challenger_list as $key => $value){
                print $value;
                if($key == (count($challenger_list)-1)){
                    $challengers .= " & ".$value;
                } else {
                    $challengers .= $value.", ";
                }
            }
        }
       // var_dump($challenger_list);
        $team_data['challengers'] = $challengers;





        return $team_data;


    }

    public function tap_form_data($form_data){

        ob_start();
        
        if(@$_GET['fid'] == 4763){ //voting
            $this->voting($form_data);
            print "voting";
        } else if (@$_GET['fid'] == 5){
           
           $this->newEntry($form_data);
          
        }





        return ob_get_clean();

    } 

    public function voting(){

    }
 
    public function newEntry($form_data){
        // this converts an submitted entry into an approved entry
        global $wpdb;

    // variables for the post_insert.
    $timestamp = date('Y-m-d G:i:s');
    $slug = $this->slugify($form_data['game_name']);
    $title = $form_data['game_name'];
    $description = $form_data['game_desc'];
    $excerpt = @$form_data['game_summary'];

    $last_post_id = $wpdb->get_var("select max(ID) as last_key from wp_posts");
    $last_title = $wpdb->get_var("select post_title from wp_posts where ID = $last_post_id");
    $next_post_id = intval($last_post_id)+1;
   print  $challengers = $form_data['challengers'];


      $insert_post = "INSERT ignore INTO `wp_posts` (ID, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES ";
 $insert_post .= "(0, 1, '$timestamp', '$timestamp', '$description', '$title', '$excerpt', 'publish', 'closed', 'closed', '', '$slug', '', '', '$timestamp', '$timestamp', '', 0, '?post_type=entry&#038;p=', 0, 'entry', '', 0)";

//wp_insert_post($insert_post);
///print $insert_post;
//$wpdb->query( $wpdb->prepare($insert_post,"ID"));

    if($title != $last_title){ // hack to prevent duplicate entry if title is same as last insert.


     print  $new_id = wp_insert_post(array(
       
            'post_author'=>1,
            'post_date'=>"$timestamp",
            'post_date_gmt'=>"$timestamp",
            'post_content'=>"$description",
            'post_title'=>"$title",
            'post_excerpt'=>"$excerpt",
            'post_status'=>'publish',
            'comment_status'=>'closed',
            'ping_status'=>'closed',
            'post_password'=>'',
            'post_name'=>"$slug",
            'to_ping'=>'',
            'pinged'=>'',
            'post_modified'=>"$timestamp",
            'post_modified_gmt'=>"$timestamp",
            'post_content_filtered'=>'',
            'post_parent'=>'0',
            'guid'=>'?post_type=entry&#038;p=',
            'menu_order'=>0,
            'post_type'=>'entry',
            'post_mime_type'=>'',
            'comment_count'=>0
            )
        );

     $wpdb->query("update wp_posts set guid = '?post_type=entry&#038;p=$new_id' where ID= $new_id");
    


   $wpdb->insert("wp_postmeta",array(
        "post_id" => $new_id,
        "meta_key"=>"challengers",
        "meta_value"=>$challengers

    ));




    }
           /*  */


    ?>


    <?php

                           
                        

    }


}
