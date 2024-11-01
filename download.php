<?php
global $wpdb;  
$date_stamp = date("Ymd"); 
$file_name = "Support_Data" . "_" . "$date_stamp" . ".xls"; 

/* Specify MIME type for tab-separated-values... */ 
// header("Content-type: text/tab-separated-values"); 

/* To open file directly into Excel, use this MIME type instead... */ 
header("Content-type: application/x-msexcel"); 

/* To force file download... */ 
header("Content-Disposition: attachment; filename=$file_name"); 

/* List the spreadsheet column labels (optional)... */ 
$column_labels = array("Support ID", "Request Title", "Date", "Field",  "Description");   /* ...and so on */ 
foreach($column_labels as $value) { 
  echo("$value\t"); 
} 
echo("\n"); 
/* ...end column labels row */ 



$query = "SELECT post_id, post_title, post_date, meta_key, meta_value FROM ".$wpdb->prefix."posts, ".$wpdb->prefix."postmeta
    WHERE   ".$wpdb->prefix."posts.ID = ".$wpdb->prefix."postmeta.post_id
     AND ".$wpdb->prefix."posts.post_type = 'support' 
      AND ".$wpdb->prefix."posts.post_status = 'publish' 
	  AND ".$wpdb->prefix."postmeta.meta_key != 'email_users' 
	  AND ".$wpdb->prefix."postmeta.meta_key != 'post_views_count' 
	  AND ".$wpdb->prefix."postmeta.meta_key != '_edit_lock' 
	  AND ".$wpdb->prefix."postmeta.meta_key != '_edit_last' 
    ORDER BY ".$wpdb->prefix."posts.post_date DESC ";	

/* Execute the query... */ 
$result = mysql_query($query); 

/* Format data as tab-separated values... */ 
while($row = mysql_fetch_array($result, MYSQL_ASSOC)) { 
  while (list($key, $value) = each($row)) { 
 
  if($value=="_simple_author_email")
  $value= "Support Author E-mail";
  if($value=="_simple_author")
  $value= "Support Author";
    if($value=="_simple_approved")
  $value= "Approval Status";
     if($value=="_simple_support_type")
  $value= "Support Description";
   if($value=="_simple_approver")
  $value= "Support";
   if($value=="_simple_approver_email")
  $value= "Approver E-mail";
    
   echo ("$value" . "\t"); 
  }         
  echo ("\n"); 
} 
?> 
