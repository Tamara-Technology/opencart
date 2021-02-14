<?php echo $header ?>
<style type="text/css">
    .modal {
        display:    none;
        position:   fixed;
        z-index:    1000;
        top:        0;
        left:       0;
        height:     100%;
        width:      100%;
        background: rgba( 255, 255, 255, .8 )
        url('http://i.stack.imgur.com/FhHRx.gif')
        50% 50%
        no-repeat;
    }

    /* When the body has the loading class, we turn
       the scrollbar off with overflow:hidden */
    body.loading .modal {
        overflow: hidden;
    }

    /* Anytime the body has the loading class, our
       modal element will be visible */
    body.loading .modal {
        display: block;
    }
</style>
<div id="common-success" class="container">
    <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb): ?>
            <li><a href="<?php echo $breadcrumb['href'] ?>"><?php echo $breadcrumb['text'] ?></a></li>
        <?php endforeach ?>
    </ul>
    <div class="row"><?php echo $column_left ?>
        <?php if ($column_left && $column_right): ?>
            <?php $class = 'col-sm-6'; ?>
        <?php elseif ($column_left || $column_right): ?>
            <?php $class = 'col-sm-9'; ?>
        <?php else: ?>
            <?php $class = 'col-sm-12'; ?>
        <?php endif ?>

        <div id="content" class="<?php echo $class ?>"><?php echo $content_top ?>
            <h1><?php echo $heading_title ?></h1>
            <?php echo $text_message ?>
            <div class="buttons">
                <div class="pull-right"><a href="<?php echo $continue ?>" class="btn btn-primary"><?php echo $button_continue ?></a></div>
            </div>
            <?php echo $content_bottom ?>
            <input type="hidden" id="order_id" name="order_id" value="<?php echo $order_id ?>" />
        </div>
        <?php echo $column_right ?>
    </div>
</div>
<div class="modal">
</div>
<?php echo $footer ?>