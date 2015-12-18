<?php
/**
 * Topline Plugin Array Stepping Helper class
 * Author:   DJF
 * Company:  30Lines
 * Purpose:  Provides helper methods to assist with navigating or manipulating arrays
 */
include_once('topline_OptionsManager.php');
class topline_ArraySteps {

    private $all;
    private $count;
    private $curr;

    public function __construct ($all) {
      $this->options = new topline_OptionsManager();
      $this->all = $all;
      $this->count = count($this->all);
    }

    public function add($step) {

      $this->count++;
      $this->all[$this->count] = $step;

    }

    public function setArrayVal($list) {
      $this->all = $list;
      return true;
    }

    public function setCurrent($val) {
      reset($this->all);
      foreach ($this->all as $key => $value) {
        if($this->all[$key]===$val) break;
        next($this->all);
      }
      $this->curr = current($this->all);
      return $this->curr;
    }

    public function getCurrent() {

      return $this->curr;

    }

    public function getPrev() {
      $this->setCurrent(current($this->all));
      return prev($this->all);
    }

    public function getNext() {
      $this->setCurrent(current($this->all));
      return next($this->all);

    }

    public function resetList($list)
    {
      if(isset($list)) $this->all = $list;
      return reset($this->all);
    }

  }
 ?>
