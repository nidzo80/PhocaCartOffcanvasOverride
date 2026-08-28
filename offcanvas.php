<?php
/**
 * @package     Joomla Template Override
 * @subpackage  mod_phocacart_cart
 * @description Cart offcanvas sa inline delete dugmetom
 *
 * Placement: /templates/YOUR_TEMPLATE/html/mod_phocacart_cart/offcanvas.php
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die;

HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('bootstrap.collapse', '');
?>
<style>
.ph-cart-offcanvas-item__tax {
    font-size: 0.72rem;
    color: #aaa;
}
.ph-cart-offcanvas-total--tax {
    font-size: 0.85rem;
    font-weight: 500;
    opacity: 0.7;
    border-top: none;
    padding-top: 8px;
    padding-bottom: 0;
}
</style>
<?php

$price        = new PhocacartPrice();
$total        = $cart->getCartTotalItems();
$cartCount    = $cart->getCartCountItems();
$checkoutLink = Route::_(PhocacartRoute::getCheckoutRoute());
$returnUrl    = base64_encode(Uri::current());
$showTaxInfo  = !empty($cart->params['display_product_tax_info']);

function pcExtractFloat($val, string $prefer = ''): float {
    if (!is_array($val)) { return (float)$val; }
    if ($prefer !== '' && isset($val[$prefer]) && !is_array($val[$prefer])) {
        return (float)$val[$prefer];
    }
    foreach ($val as $v) {
        if (!is_array($v)) { return (float)$v; }
    }
    $first = reset($val);
    return is_array($first) ? pcExtractFloat($first, $prefer) : (float)$first;
}

// Checkout link — isti kao $d['linkcheckout'] u cart_checkout.php
$linkCheckout = PhocacartRoute::getCheckoutRoute();

// Cart IDs — isti kao u cart_checkout.php
$ticketId  = $cart->getTicketId();
$unitId    = $cart->getUnitId();
$sectionId = $cart->getSectionId();

// getFullItems() vraća [ sectionId => [ 'idkey::' => [...] ] ]
// Mergeamo u flat array, svaki idkey samo jednom
$fullItemsRaw = $cart->getFullItems();
$fullItems    = array();
if (!empty($fullItemsRaw)) {
    foreach ($fullItemsRaw as $section) {
        foreach ($section as $key => $item) {
            if (!isset($fullItems[$key])) {
                $fullItems[$key] = $item;
            }
        }
    }
}
?>

<div class="ph-cart-module-box ph-cart-module-cart-box <?php echo $moduleclass_sfx; ?>">

    <div class="ph-cart-module-cart-box-info">
        <div class="ph-cart-module-cart-box-info-title"><?php echo Text::_('COM_PHOCACART_SHOPPING_CART'); ?></div>
        <div class="ph-cart-module-cart-box-info-amount phItemCartBoxTotal"><?php echo $price->getPriceFormat($total[0]['brutto'] ?? 0); ?></div>
    </div>

    <button id="phItemCartBoxBtn"
            data-bs-toggle="offcanvas"
            data-bs-target="#phItemCartBoxOffCanvas"
            aria-controls="phItemCartBoxOffCanvas"
            title="<?php echo Text::_('COM_PHOCACART_DISPLAY_SHOPPING_CART'); ?>">
        <?php echo PhocacartRenderIcon::icon($s['i']['shopping-cart']); ?>
        <sup class="ph-cart-count-sup phItemCartBoxCount" id="phItemCartBoxCount"><?php echo $cartCount; ?></sup>
    </button>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="phItemCartBoxOffCanvas" aria-labelledby="phItemCartBoxOffCanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="phItemCartBoxOffCanvasLabel">
                <?php echo Text::_('COM_PHOCACART_SHOPPING_CART'); ?>
            </h5>
            <button type="button" class="btn-close text-reset"
                    data-bs-dismiss="offcanvas"
                    aria-label="<?php echo Text::_('COM_PHOCACART_CLOSE'); ?>">
            </button>
        </div>

        <div class="offcanvas-body">

            <?php if (empty($fullItems)) : ?>

                <div class="ph-cart-offcanvas-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.3">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <p><?php echo Text::_('COM_PHOCACART_SHOPPING_CART_IS_EMPTY'); ?></p>
                </div>

            <?php else : foreach ($fullItems as $key => $item) :

                $productId = (int)$item['id'];
                $catid     = (int)$item['catid'];
                $quantity  = (int)$item['quantity'];
                $title     = $item['title'];
                $alias     = $item['alias'] ?? '';
                $idkey     = $item['idkey'] ?? $key;
                $brutto    = pcExtractFloat($item['brutto'] ?? 0, 'brutto');
                $netto     = pcExtractFloat($item['netto']  ?? 0, 'netto');
                $tax       = pcExtractFloat($item['tax']    ?? 0, 'tax');

                // Ako showTaxInfo — prikaži netto kao cijenu stavke (kao default modul)
                // Ako ne — prikaži brutto
                $displayPrice = ($showTaxInfo && $netto > 0) ? $netto : $brutto;

                $link      = Route::_(PhocacartRoute::getItemRoute($productId, $catid, $alias, ''));
                $itemPrice = $price->getPriceFormat($displayPrice * (int)$quantity);
                $taxPrice  = ($showTaxInfo && $tax > 0) ? $price->getPriceFormat($tax * (int)$quantity) : '';

                // Slika — image sadrži "Futrole_za_telefon/futrola za telefon 1.jpeg"
                $imageSrc = '';
                if (!empty($item['image'])) {
                    $imgDir   = dirname($item['image']);
                    $imgFile  = basename($item['image']);
                    $basePath = 'images/phocacartproducts/' . $imgDir . '/thumbs/';
                    $small    = $basePath . 'phoca_thumb_s_' . $imgFile;
                    $medium   = $basePath . 'phoca_thumb_m_' . $imgFile;
                    if (file_exists(JPATH_ROOT . '/' . $small)) {
                        $imageSrc = Uri::root() . $small;
                    } elseif (file_exists(JPATH_ROOT . '/' . $medium)) {
                        $imageSrc = Uri::root() . $medium;
                    }
                }

            ?>

                <div class="ph-cart-offcanvas-item">

                    <?php if (!empty($imageSrc)) : ?>
                        <a href="<?php echo $link; ?>" class="ph-cart-offcanvas-item__img-link">
                            <img src="<?php echo $imageSrc; ?>"
                                 alt="<?php echo htmlspecialchars($title); ?>"
                                 loading="lazy"
                                 class="ph-cart-offcanvas-item__img" />
                        </a>
                    <?php endif; ?>

                    <div class="ph-cart-offcanvas-item__info">
                        <a href="<?php echo $link; ?>" class="ph-cart-offcanvas-item__title">
                            <?php echo htmlspecialchars($title); ?>
                        </a>
                        <div class="ph-cart-offcanvas-item__meta">
                            <span class="ph-cart-offcanvas-item__qty"><?php echo $quantity; ?>x</span>
                            <span class="ph-cart-offcanvas-item__price"><?php echo $itemPrice; ?></span>
                            <?php if ($showTaxInfo && !empty($taxPrice)) : ?>
                            <span class="ph-cart-offcanvas-item__tax"><?php echo $taxPrice; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!--
                        Delete forma — identična strukturi iz cart_checkout.php:
                        action  = checkout link
                        task    = checkout.update
                        action  = delete
                        Uključuje idkey, ticketid, unitid, sectionid
                    -->
                    <form action="<?php echo Route::_($linkCheckout); ?>"
                          method="post"
                          class="ph-cart-remove-form phItemCartUpdateBoxForm">
                        <input type="hidden" name="id"        value="<?php echo $productId; ?>">
                        <input type="hidden" name="catid"     value="<?php echo $catid; ?>">
                        <input type="hidden" name="idkey"     value="<?php echo htmlspecialchars($idkey); ?>">
                        <input type="hidden" name="ticketid"  value="<?php echo (int)$ticketId; ?>">
                        <input type="hidden" name="unitid"    value="<?php echo (int)$unitId; ?>">
                        <input type="hidden" name="sectionid" value="<?php echo (int)$sectionId; ?>">
                        <input type="hidden" name="task"      value="checkout.update">
                        <input type="hidden" name="tmpl"      value="component">
                        <input type="hidden" name="option"    value="com_phocacart">
                        <input type="hidden" name="return"    value="<?php echo $returnUrl; ?>">
                        <?php echo HTMLHelper::_('form.token'); ?>
                        <button type="submit"
                                name="action"
                                value="delete"
                                class="ph-cart-remove-btn"
                                title="<?php echo Text::_('COM_PHOCACART_REMOVE_PRODUCT_FROM_CART'); ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <path d="M10 11v6M14 11v6"/>
                                <path d="M9 6V4h6v2"/>
                            </svg>
                        </button>
                    </form>

                </div><!-- /.ph-cart-offcanvas-item -->

            <?php endforeach; ?>

                <!-- Ukupno -->
                <?php
                $totalTax    = pcExtractFloat($total[0]['tax']    ?? 0, 'tax');
                $totalBrutto = pcExtractFloat($total[0]['brutto'] ?? 0, 'brutto');
                if ($showTaxInfo && $totalTax > 0) : ?>
                <div class="ph-cart-offcanvas-total ph-cart-offcanvas-total--tax">
                    <span><?php echo Text::_('COM_PHOCACART_TAX'); ?></span>
                    <span class="ph-cart-offcanvas-total__amount"><?php echo $price->getPriceFormat($totalTax); ?></span>
                </div>
                <?php endif; ?>
                <div class="ph-cart-offcanvas-total">
                    <span><?php echo Text::_('COM_PHOCACART_TOTAL'); ?></span>
                    <span class="ph-cart-offcanvas-total__amount">
                        <?php echo $price->getPriceFormat($totalBrutto); ?>
                    </span>
                </div>

                <!-- Checkout -->
                <a href="<?php echo $checkoutLink; ?>" class="ph-cart-offcanvas-checkout">
                    <?php echo Text::_('COM_PHOCACART_CHECKOUT'); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>

            <?php endif; ?>

        </div>{module title="Upsell"}<!-- /.offcanvas-body -->
    </div><!-- /.offcanvas -->

</div><!-- /.ph-cart-module-box -->

<script>
jQuery(document).ready(function () {

    // Kada Phoca Cart AJAX ažurira cart count, osvježavamo offcanvas body
    function lxRefreshOffcanvasCart() {
        fetch(window.location.href)
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var parser  = new DOMParser();
                var doc     = parser.parseFromString(html, 'text/html');
                var newBody = doc.querySelector('#phItemCartBoxOffCanvas .offcanvas-body');
                var curBody = document.querySelector('#phItemCartBoxOffCanvas .offcanvas-body');
                if (newBody && curBody) {
                    curBody.innerHTML = newBody.innerHTML;
                }
            })
            .catch(function () {});
    }

    // MutationObserver — osvježava offcanvas kada Phoca Cart AJAX ažurira count
    var countEl = document.getElementById('phItemCartBoxCount');
    if (countEl) {
        var lxCartObserver = new MutationObserver(function () {
            lxRefreshOffcanvasCart();
        });
        lxCartObserver.observe(countEl, { childList: true, subtree: true, characterData: true });
    }

    // AJAX brisanje — intercept delete submit
    jQuery(document).on('click', '#phItemCartBoxOffCanvas .ph-cart-remove-btn', function (e) {

        e.preventDefault();
        e.stopPropagation();

        var $btn  = jQuery(this);
        var $form = $btn.closest('form.phItemCartUpdateBoxForm');
        var $item = $btn.closest('.ph-cart-offcanvas-item');

        $item.css({ opacity: 0.4, pointerEvents: 'none' });

        var formData = $form.serialize() + '&action=delete';

        if (typeof phDoSubmitFormUpdateCart === 'function') {
            phDoSubmitFormUpdateCart(formData);
        } else {
            // Uzmi URL iz forme ali dodaj tmpl=component da izbjegnemo redirect
            var actionUrl = $form.attr('action');
            // Ukloni eventualni tmpl parametar i dodaj component
            actionUrl = actionUrl.replace(/[?&]tmpl=[^&]*/g, '');
            actionUrl += (actionUrl.indexOf('?') >= 0 ? '&' : '?') + 'tmpl=component';

            jQuery.ajax({
                url: actionUrl,
                type: 'POST',
                data: formData,
                complete: function () {
                    lxRefreshOffcanvasCart();
                }
            });
        }

        return false;
    });

});
</script>
