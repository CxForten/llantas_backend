<?php

return [
    'iva_rate'      => (int) env('LLANTERA_IVA_RATE', 15),
    'card_fee_pct'  => (int) env('LLANTERA_CARD_FEE_PCT', 15),
    'margin_main'   => (int) env('LLANTERA_MARGIN_MAIN', 25),
    'margin_alt'    => (int) env('LLANTERA_MARGIN_ALT', 20),

 
    'calc_order'    => env('LLANTERA_CALC_ORDER', 'discount_first'),
];