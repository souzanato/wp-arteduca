<?php
/**
 * Render callback: bebelume/terreo
 */

$wrapper_attrs = get_block_wrapper_attributes( array(
    'class' => 'bebelume-nivel w-100 branco',
) );
?>
<section <?php echo $wrapper_attrs; ?>>

    <div class="garden-container">
        <div class="colinas">
            <div class="colina colina1"></div>
            <div class="colina colina2"></div>
            <div class="colina colina3"></div>
        </div>
        <div class="campo"></div>
        <div class="borboleta borboleta1">
            <div class="asa asa-esquerda"></div>
            <div class="asa asa-direita"></div>
            <div class="corpo-borboleta"></div>
        </div>
        <div class="borboleta borboleta2">
            <div class="asa asa-esquerda"></div>
            <div class="asa asa-direita"></div>
            <div class="corpo-borboleta"></div>
        </div>
        <div class="borboleta borboleta3">
            <div class="asa asa-esquerda"></div>
            <div class="asa asa-direita"></div>
            <div class="corpo-borboleta"></div>
        </div>
        <div class="flores-container">
            <div class="arbustos">
                <div class="arbusto" style="left:0%"></div>
                <div class="arbusto" style="left:10%;bottom:-6em"></div>
                <div class="arbusto" style="left:80%"></div>
                <div class="arbusto" style="left:90%;bottom:-6em"></div>
            </div>
            <div class="plantinhas">
                <div class="plantinha-bebelume" style="left:5%"></div>
                <div class="plantinha-bebelume" style="left:20%"></div>
                <div class="plantinha-bebelume" style="left:35%"></div>
                <div class="plantinha-bebelume" style="left:50%"></div>
                <div class="plantinha-bebelume" style="left:65%"></div>
                <div class="plantinha-bebelume" style="left:80%"></div>
            </div>
        </div>
    </div>

    <div class="bebelume-andar terreo-casinha-bebelume has-image">
        <div class="andar-content">
            <div class="row bebelume-level-teasers">
                <?php echo $content; ?>
            </div>
        </div>
    </div>

</section>
