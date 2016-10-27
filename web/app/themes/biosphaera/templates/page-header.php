<?php use Roots\Sage\Titles;use Roots\Sage\Setup; ?>

<div class="page-header">
  <h1 <?php if(!Setup\display_title()) echo'class="screen-reader"'; ?> ><?= Titles\title(); ?></h1>
</div>
