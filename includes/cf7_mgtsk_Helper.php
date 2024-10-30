<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of helper
 *
 * @author arizona
 */
class cf7_mgtsk_Helper
{

  public static function cleanFormContent($cRaw)
  {
    $cClean = cf7_mgtsk_Helper::cleanFormContentRegexp($cRaw);
    $c = array();
    foreach ($cClean[1] as $i => $raw) {
      $raw = cf7_mgtsk_Helper::cleanFormLineRaw($raw);
      $field = explode(' ', $raw);

      $type = $field[0];
      $name = $field[1];

      if ($type == 'submit') {
        continue;
      }

      $display_name = '';
      if (array_search('placeholder', $field)) {
        $display_name = $field[array_search('placeholder', $field) + 1];
        $display_name = str_replace('_', ' ', $display_name);
      }

      $required = 0;
      if (preg_match('/\*$/', $type) > 0) {
        $required = 1;
        $type = preg_replace('/\*$/', '', $type);
      }

      $c[$i] = array(
        'type' => $type,
        'required' => $required,
        'name' => $name,
        'display_name' => $display_name
      );
    }
    return $c;
  }

  public static function cleanFormContentRegexp($cRaw)
  {
    $pattern = '/\[(.*?)\]/m';
    preg_match_all($pattern, $cRaw, $cClean);
    return $cClean;
  }

  public static function cleanFormLineRaw($lRaw)
  {
    preg_match_all('/"(.*?)"/', $lRaw, $string);
    if (!empty($string[1])) {
      $replacement = str_replace(' ', '_', $string[1][0]);
      $lRaw = preg_replace('/"(.*?)"/', $replacement, $lRaw);
    }
    return $lRaw;
  }

  public static function showTable($c, $use_mgtsk)
  {
    ob_start();
    ?>
    <table class="table-small table-properties">
      <thead>
      <tr>
        <td><?php _e('type', 'cf7_mgtsk_add'); ?></td>
        <?php if (1 == 0) { ?>
          <td>*</td>
        <?php } ?>
        <td><?php _e('name', 'cf7_mgtsk_add'); ?></td>
        <td><?php _e('display_name', 'cf7_mgtsk_add'); ?></td>
      </tr>
      </thead>
      <tbody>
      <?php
      foreach ($c as $row) {
        $class = array();
        if ($row['required'] == 1) {
          $class[] = 'required';
        }
        if ($use_mgtsk == true) {
          $re = "/(email|phone)/";
          $str = $row['name'];
          preg_match($re, $str, $matches);

          if (count($matches) > 0) {
//          $class[] = 'field';
          }
        }
        ?>
        <tr class="<?php echo implode(' ', $class); ?>">
          <td><?php echo $row['type']; ?></td>
          <?php if (1 == 0) { ?>
            <td><?php echo $row['required']; ?></td>
          <?php } ?>
          <td><?php echo $row['name']; ?></td>
          <td><?php echo $row['display_name']; ?></td>
        </tr>
        <?php
      }
      ?>
      </tbody>
    </table>
    <?php
    return ob_get_clean();
  }

  public static function streamComment($arr)
  {
    $resultRaw = array();
    foreach ($arr as $line) {
      if (empty($line['name'])) {
        $resultRaw[] = $line['value'];
      } else {
        $resultRaw[] = $line['name'] . ': ' . $line['value'];
      }
    }
    return (implode("<br/>" . PHP_EOL, $resultRaw));
  }

}
