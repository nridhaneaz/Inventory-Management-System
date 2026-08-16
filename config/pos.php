<?php

return [
    'business' => [
        'name' => env('POS_BUSINESS_NAME', 'CITY BAKEWARE TRADE'),
        'name_bn' => env('POS_BUSINESS_NAME_BN', 'সিটি বেকওয়্যার ট্রেড'),
        'description_bn' => env(
            'POS_BUSINESS_DESCRIPTION_BN',
            'বেকিং সামগ্রী, কিচেন সামগ্রী, বিটার, ওভেন, কেকের সরঞ্জাম, প্যাকিং সামগ্রী, কেকের কাঁচামাল ইত্যাদি পাইকারি ও খুচরা বিক্রয়'
        ),
        'proprietor_bn' => env('POS_PROPRIETOR_BN', 'মোঃ শেখ রিয়াদুল ইসলাম'),
        'proprietor_title_bn' => 'প্রোপাইটর',
        'address_bn' => env('POS_BUSINESS_ADDRESS_BN', 'ছাপড়া মসজিদ, আজিমপুর, ঢাকা-১২০৫।'),
        'phones' => array_filter(array_map('trim', explode(',', env('POS_BUSINESS_PHONES', '01849-534270,01576-975785')))),
        'logo' => env('POS_BUSINESS_LOGO', null),
    ],
];
