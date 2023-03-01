<li>
    <div class="navbar-lang-btn">
        <?php
        if ($lang == 'en') {
            $lang = 'en_US';
        }
        echo Layout::getLangsSelect('navBarFlag', $lang, 'navBarFlag', '', true);
        //var_dump($lang);exit;
        ?>

    </div>
</li>
<script>
    $(function () {
        $("#navBarFlag").change(function () {
            var selfURI = "<?php echo getSelfURI(); ?>";
            window.location.href = addGetParam(selfURI, 'lang', $(this).val());
        });
    });
</script>