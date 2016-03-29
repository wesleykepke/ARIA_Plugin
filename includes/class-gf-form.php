<?php

if ( ! class_exists( 'GFForms' ) ) {
  die();
}

class GF_Form {
  // Basic Properties
  public $id = null;
  public $title = "";
  public $description = "";
  public $labelPlacement = "top_label";
  public $descriptionPlacement = "below";
  public $fields = array();

  // Post Related Properties
  public $useCurrentUserAsAuthor = true;
  public $postContentTemplate = "";
  public $postContentTemplateEnabled = false;
  public $postTitleTemplate = "";
  public $postTitleTemplateEnabled = false;

  // Form Submission
  public $confirmation = array();
  public $notifications = array();

  // Advanced Properties
  public $button = array("type" => "text", "text" => "submit");
  public $cssClass = null;

  public function __construct($title, $description) {
    $this->title = $title;
    $this->description = $description;
  }

  public function createFormArray() {
    $form = array();

    $form["id"] = $this->id;
    $form["title"] = $this->title;
    $form["description"] = $this->description;
    $form["labelPlacement"] = $this->labelPlacement;
    $form["descriptionPlacement"] = $this->descriptionPlacement;
    $form["fields"] = $this->fields;
    $form["useCurrentUserAsAuthor"] = $this->useCurrentUserAsAuthor;
    $form["postContentTemplate"] = $this->postContentTemplate;
    $form["postContentTemplateEnabled"] = $this->postContentTemplateEnabled;
    $form["postTitleTemplate"] = $this->postTitleTemplate;
    $form["postTitleTemplateEnabled"] = $this->postTitleTemplateEnabled;
    $form["confirmation"] = $this->confirmation;
    $form["notifications"] = $this->notifications;
    $form["button"] = $this->button;
    $form["cssClass"] = $this->cssClass;

    return $form;
  }
};






