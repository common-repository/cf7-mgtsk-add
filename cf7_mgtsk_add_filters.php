<?php
add_filter('cf7_mgtsk_acceptor_filter', 'cf7_mgtsk_add_filters_acceptor', 10);
add_filter('cf7_mgtsk_values_filter', 'cf7_mgtsk_add_filters_values', 10);

function cf7_mgtsk_add_filters_test()
{
  ?>
  la-la
  <?php
}

function cf7_mgtsk_add_filters_acceptor()
{
  return 'http://acceptor.mgtsk.ru/lead-catcher/catch';
//    return 'http://samsa.pro/cf7_mgtsk_add.php';
}

function cf7_mgtsk_add_filters_values($values)
{

  $linking = array(
    'email' => "Email",
    'comment' => "Comment",
    'phone' => "Phone",
    'first-name' => 'FirstName'

  );
  $scheme = 'http';
  $data = array(
//    "Site" => get_site_url(),
    "Site" => str_replace(array('http://', 'https://'), '', get_site_url())
  );


  foreach ($linking as $cformField => $mtField) {
    if (isset($values[$cformField])) {
      $value = trim($values[$cformField]);
      if ($value != "") {
        $data[$mtField] = $value;
      }
    }
  }

  return array(
    'key' => $values['mgtsk_key'],
    'data' => $data
  );
}