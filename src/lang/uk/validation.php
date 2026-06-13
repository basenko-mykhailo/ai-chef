<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Поле :attribute має бути прийняте.',
    'accepted_if' => 'Поле :attribute має бути прийняте, коли :other дорівнює :value.',
    'active_url' => 'Поле :attribute має бути коректним URL.',
    'after' => 'Поле :attribute має бути датою після :date.',
    'after_or_equal' => 'Поле :attribute має бути датою після або рівною :date.',
    'alpha' => 'Поле :attribute має містити лише літери.',
    'alpha_dash' => 'Поле :attribute має містити лише літери, цифри, дефіси та підкреслення.',
    'alpha_num' => 'Поле :attribute має містити лише літери та цифри.',
    'any_of' => 'Поле :attribute є некоректним.',
    'array' => 'Поле :attribute має бути масивом.',
    'ascii' => 'Поле :attribute має містити лише однобайтові літерно-цифрові символи та знаки.',
    'before' => 'Поле :attribute має бути датою до :date.',
    'before_or_equal' => 'Поле :attribute має бути датою до або рівною :date.',
    'between' => [
        'array' => 'Поле :attribute має містити від :min до :max елементів.',
        'file' => 'Файл :attribute має бути розміром від :min до :max кілобайтів.',
        'numeric' => 'Поле :attribute має бути від :min до :max.',
        'string' => 'Поле :attribute має містити від :min до :max символів.',
    ],
    'boolean' => 'Поле :attribute має бути true або false.',
    'can' => 'Поле :attribute містить недозволене значення.',
    'confirmed' => 'Підтвердження поля :attribute не збігається.',
    'contains' => 'У полі :attribute відсутнє обов\'язкове значення.',
    'current_password' => 'Пароль введено неправильно.',
    'date' => 'Поле :attribute має бути коректною датою.',
    'date_equals' => 'Поле :attribute має бути датою, рівною :date.',
    'date_format' => 'Поле :attribute має відповідати формату :format.',
    'decimal' => 'Поле :attribute має містити :decimal знаків після коми.',
    'declined' => 'Поле :attribute має бути відхилене.',
    'declined_if' => 'Поле :attribute має бути відхилене, коли :other дорівнює :value.',
    'different' => 'Поля :attribute та :other мають відрізнятися.',
    'digits' => 'Поле :attribute має містити :digits цифр.',
    'digits_between' => 'Поле :attribute має містити від :min до :max цифр.',
    'dimensions' => 'Поле :attribute має некоректні розміри зображення.',
    'distinct' => 'Поле :attribute має повторюване значення.',
    'doesnt_contain' => 'Поле :attribute не має містити жодного з: :values.',
    'doesnt_end_with' => 'Поле :attribute не має закінчуватися одним із: :values.',
    'doesnt_start_with' => 'Поле :attribute не має починатися одним із: :values.',
    'email' => 'Поле :attribute має бути коректною електронною адресою.',
    'encoding' => 'Поле :attribute має бути в кодуванні :encoding.',
    'ends_with' => 'Поле :attribute має закінчуватися одним із: :values.',
    'enum' => 'Обране значення :attribute є некоректним.',
    'exists' => 'Обране значення :attribute є некоректним.',
    'extensions' => 'Поле :attribute має мати одне з розширень: :values.',
    'file' => 'Поле :attribute має бути файлом.',
    'filled' => 'Поле :attribute має містити значення.',
    'gt' => [
        'array' => 'Поле :attribute має містити більше ніж :value елементів.',
        'file' => 'Файл :attribute має бути більшим за :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути більшим за :value.',
        'string' => 'Поле :attribute має містити більше ніж :value символів.',
    ],
    'gte' => [
        'array' => 'Поле :attribute має містити :value елементів або більше.',
        'file' => 'Файл :attribute має бути більшим або рівним :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути більшим або рівним :value.',
        'string' => 'Поле :attribute має містити :value символів або більше.',
    ],
    'hex_color' => 'Поле :attribute має бути коректним шістнадцятковим кольором.',
    'image' => 'Поле :attribute має бути зображенням.',
    'in' => 'Обране значення :attribute є некоректним.',
    'in_array' => 'Поле :attribute має існувати в :other.',
    'in_array_keys' => 'Поле :attribute має містити принаймні один із ключів: :values.',
    'integer' => 'Поле :attribute має бути цілим числом.',
    'ip' => 'Поле :attribute має бути коректною IP-адресою.',
    'ipv4' => 'Поле :attribute має бути коректною IPv4-адресою.',
    'ipv6' => 'Поле :attribute має бути коректною IPv6-адресою.',
    'json' => 'Поле :attribute має бути коректним JSON-рядком.',
    'list' => 'Поле :attribute має бути списком.',
    'lowercase' => 'Поле :attribute має бути в нижньому регістрі.',
    'lt' => [
        'array' => 'Поле :attribute має містити менше ніж :value елементів.',
        'file' => 'Файл :attribute має бути меншим за :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути меншим за :value.',
        'string' => 'Поле :attribute має містити менше ніж :value символів.',
    ],
    'lte' => [
        'array' => 'Поле :attribute не має містити більше ніж :value елементів.',
        'file' => 'Файл :attribute має бути меншим або рівним :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути меншим або рівним :value.',
        'string' => 'Поле :attribute має містити :value символів або менше.',
    ],
    'mac_address' => 'Поле :attribute має бути коректною MAC-адресою.',
    'max' => [
        'array' => 'Поле :attribute не має містити більше ніж :max елементів.',
        'file' => 'Файл :attribute не має бути більшим за :max кілобайтів.',
        'numeric' => 'Поле :attribute не має бути більшим за :max.',
        'string' => 'Поле :attribute не має бути довшим за :max символів.',
    ],
    'max_digits' => 'Поле :attribute не має містити більше ніж :max цифр.',
    'mimes' => 'Поле :attribute має бути файлом типу: :values.',
    'mimetypes' => 'Поле :attribute має бути файлом типу: :values.',
    'min' => [
        'array' => 'Поле :attribute має містити принаймні :min елементів.',
        'file' => 'Файл :attribute має бути не меншим за :min кілобайтів.',
        'numeric' => 'Поле :attribute має бути не меншим за :min.',
        'string' => 'Поле :attribute має містити принаймні :min символів.',
    ],
    'min_digits' => 'Поле :attribute має містити принаймні :min цифр.',
    'missing' => 'Поле :attribute має бути відсутнім.',
    'missing_if' => 'Поле :attribute має бути відсутнім, коли :other дорівнює :value.',
    'missing_unless' => 'Поле :attribute має бути відсутнім, якщо :other не дорівнює :value.',
    'missing_with' => 'Поле :attribute має бути відсутнім, коли присутнє :values.',
    'missing_with_all' => 'Поле :attribute має бути відсутнім, коли присутні :values.',
    'multiple_of' => 'Поле :attribute має бути кратним :value.',
    'not_in' => 'Обране значення :attribute є некоректним.',
    'not_regex' => 'Формат поля :attribute є некоректним.',
    'numeric' => 'Поле :attribute має бути числом.',
    'password' => [
        'letters' => 'Поле :attribute має містити принаймні одну літеру.',
        'mixed' => 'Поле :attribute має містити принаймні одну велику та одну малу літеру.',
        'numbers' => 'Поле :attribute має містити принаймні одну цифру.',
        'symbols' => 'Поле :attribute має містити принаймні один символ.',
        'uncompromised' => 'Вказане значення :attribute з\'явилося у витоку даних. Будь ласка, оберіть інше значення :attribute.',
    ],
    'present' => 'Поле :attribute має бути присутнім.',
    'present_if' => 'Поле :attribute має бути присутнім, коли :other дорівнює :value.',
    'present_unless' => 'Поле :attribute має бути присутнім, якщо :other не дорівнює :value.',
    'present_with' => 'Поле :attribute має бути присутнім, коли присутнє :values.',
    'present_with_all' => 'Поле :attribute має бути присутнім, коли присутні :values.',
    'prohibited' => 'Поле :attribute заборонене.',
    'prohibited_if' => 'Поле :attribute заборонене, коли :other дорівнює :value.',
    'prohibited_if_accepted' => 'Поле :attribute заборонене, коли :other прийнято.',
    'prohibited_if_declined' => 'Поле :attribute заборонене, коли :other відхилено.',
    'prohibited_unless' => 'Поле :attribute заборонене, якщо :other не входить до :values.',
    'prohibits' => 'Поле :attribute забороняє присутність :other.',
    'regex' => 'Формат поля :attribute є некоректним.',
    'required' => 'Поле :attribute є обов\'язковим.',
    'required_array_keys' => 'Поле :attribute має містити записи для: :values.',
    'required_if' => 'Поле :attribute є обов\'язковим, коли :other дорівнює :value.',
    'required_if_accepted' => 'Поле :attribute є обов\'язковим, коли :other прийнято.',
    'required_if_declined' => 'Поле :attribute є обов\'язковим, коли :other відхилено.',
    'required_unless' => 'Поле :attribute є обов\'язковим, якщо :other не входить до :values.',
    'required_with' => 'Поле :attribute є обов\'язковим, коли присутнє :values.',
    'required_with_all' => 'Поле :attribute є обов\'язковим, коли присутні :values.',
    'required_without' => 'Поле :attribute є обов\'язковим, коли відсутнє :values.',
    'required_without_all' => 'Поле :attribute є обов\'язковим, коли відсутні всі :values.',
    'same' => 'Поле :attribute має збігатися з :other.',
    'size' => [
        'array' => 'Поле :attribute має містити :size елементів.',
        'file' => 'Файл :attribute має бути розміром :size кілобайтів.',
        'numeric' => 'Поле :attribute має дорівнювати :size.',
        'string' => 'Поле :attribute має містити :size символів.',
    ],
    'starts_with' => 'Поле :attribute має починатися одним із: :values.',
    'string' => 'Поле :attribute має бути рядком.',
    'timezone' => 'Поле :attribute має бути коректним часовим поясом.',
    'unique' => 'Таке значення :attribute вже зайнято.',
    'uploaded' => 'Не вдалося завантажити :attribute.',
    'uppercase' => 'Поле :attribute має бути у верхньому регістрі.',
    'url' => 'Поле :attribute має бути коректним URL.',
    'ulid' => 'Поле :attribute має бути коректним ULID.',
    'uuid' => 'Поле :attribute має бути коректним UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Зручні назви полів, що підставляються замість :attribute, щоб
    | повідомлення читались природніше (напр. «електронна пошта» замість «email»).
    |
    */

    'attributes' => [
        'name' => 'ім\'я',
        'email' => 'електронна пошта',
        'password' => 'пароль',
        'password_confirmation' => 'підтвердження пароля',
        'current_password' => 'поточний пароль',
    ],

];
