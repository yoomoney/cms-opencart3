<?php

$_['module_title'] = 'Y.CMS 2.0';
$_['heading_title'] = $_['module_title'];
$_['text_yandex_money'] = '<a target="_blank" href="https://kassa.yandex.ru"><img src="../image/catalog/payment/yandex_money/yandex_money_logo.png" alt="Y.CMS 2.0 от Яндекс.Кассы" /></a>';
$_['kassa_header_description'] = 'Работая с модулем, вы автоматически соглашаетесь с <a href="https://money.yandex.ru/doc.xml?id=527132">условиями его использования</a>.';
$_['kassa_version_string'] = 'Версия модуля';

$_['kassa_breadcrumbs_extension'] = 'Расширения';
$_['kassa_breadcrumbs_home'] = 'Главная';
$_['kassa_breadcrumbs_logs'] = 'Журнал сообщений';
$_['kassa_text_success'] = 'Success';
$_['kassa_page_title'] = 'Настройки Яндекс.Кассы';
$_['kassa_breadcrumbs_heading_title'] = 'Журнал сообщений платежного модуля Яндекс.Деньги';
$_['kassa_test_mode_description'] = 'Вы включили тестовый режим приема платежей. Проверьте, как проходит оплата, и напишите менеджеру Кассы. Он выдаст рабочие shopId и Секретный ключ. <a href="https://yandex.ru/support/checkout/payments/api.html#api__04" target="_blank">Инструкция</a>';

$_['kassa_enable_label'] = 'Включить приём платежей через Яндекс.Кассу';

$_['kassa_shop_id_label'] = 'shopId';
$_['kassa_shop_id_description'] = 'Скопируйте shopId из личного кабинета Яндекс.Кассы';
$_['kassa_shop_id_error_required'] = 'Необходимо указать shopId из личного кабинета Яндекс.Кассы';

$_['kassa_password_label'] = 'Секретный ключ';
$_['kassa_password_description'] = 'Выпустите и активируйте секретный ключ в <a href="https://kassa.yandex.ru/my" target="_blank">личном кабинете Яндекс.Кассы</a>. Потом скопируйте его сюда.';
$_['kassa_password_error_required'] = 'Необходимо указать секретный ключ из личного кабинета Яндекс.Кассы';
$_['kassa_error_invalid_credentials'] = 'Проверьте shopId и Секретный ключ — где-то есть ошибка. А лучше скопируйте их прямо из <a href="https://kassa.yandex.ru/my" target="_blank">личного кабинета Яндекс.Кассы</a>';

$_['kassa_payment_mode_label'] = 'Выбор способа оплаты';
$_['kassa_payment_mode_kassa_label'] = 'На стороне Кассы';
$_['kassa_use_yandex_button_label'] = 'Назвать кнопку оплаты «Заплатить через Яндекс»';
$_['kassa_use_installments_button_label'] = 'Добавить кнопку «Заплатить по частям» на страницу оформления заказа';
$_['kassa_payment_mode_shop_label'] = 'На стороне магазина';

$_['kassa_payment_method_bank_card'] = 'Банковские карты';
$_['kassa_payment_method_sberbank'] = 'Сбербанк Онлайн';
$_['kassa_payment_method_cash'] = 'Наличные через терминалы';
$_['kassa_payment_method_qiwi'] = 'QIWI Wallet';
$_['kassa_payment_method_alfabank'] = 'Альфа-Клик';
$_['kassa_payment_method_webmoney'] = 'Webmoney';
$_['kassa_payment_method_yandex_money'] = 'Яндекс.Деньги';
$_['kassa_payment_method_mobile'] = 'Баланс мобильного';
$_['kassa_payment_method_installments'] = 'Заплатить по частям';

$_['kassa_payment_method_error_required'] = 'Пожалуйста, выберите хотя бы один способ из списка';

$_['kassa_display_name_label'] = 'Название платежного сервиса';
$_['kassa_display_name_description'] = 'Это название увидит пользователь';
$_['kassa_default_display_name'] = 'Яндекс.Касса (банковские карты, электронные деньги и другое)';

$_['kassa_send_receipt_label'] = 'Отправлять в Яндекс.Кассу данные для чеков (54-ФЗ)';
$_['kassa_send_receipt_tax_rate_title'] = 'НДС';
$_['kassa_tax_rate_default_label'] = 'Ставка по умолчанию';
$_['kassa_tax_rate_default_description'] = 'Ставка по умолчанию будет в чеке, если в карточке товара не указана другая ставка.';
$_['kassa_tax_rate_1_label'] = 'Без НДС';
$_['kassa_tax_rate_2_label'] = '0%';
$_['kassa_tax_rate_3_label'] = '10%';
$_['kassa_tax_rate_4_label'] = '18%';
$_['kassa_tax_rate_5_label'] = 'Расчетная ставка 10/110';
$_['kassa_tax_rate_6_label'] = 'Расчетная ставка 18/118';
$_['kassa_tax_rate_table_caption'] = 'Сопоставьте ставки';
$_['kassa_shop_tax_rate_header'] = 'Ставка в вашем магазине';
$_['kassa_kassa_tax_rate_header'] = 'Ставка для чека в налоговую';

$_['kassa_notification_url_label'] = 'Адрес для уведомлений';
$_['kassa_notification_url_description'] = 'Этот адрес понадобится, только если его попросят специалисты Яндекс.Кассы';

$_['kassa_before_redirect_label'] = 'Когда пользователь переходит к оплате';
$_['kassa_create_order_label'] = 'Создать неоплаченный заказ в панели управления';
$_['kassa_clear_cart_label'] = 'Удалить товары из корзины';

$_['kassa_success_order_status_label'] = 'Статус заказа после оплаты';
$_['kassa_success_order_status_description'] = '';

$_['kassa_minimum_payment_amount_label'] = 'Минимальная сумма заказа';
$_['kassa_minimum_payment_amount_description'] = 'Сумма заказа при которой можно провести платёж с помощью Яндекс.Кассы';

$_['kassa_geo_zone_label'] = 'Регион отображения';
$_['kassa_geo_zone_description'] = 'Геозона в которой будет отображаться способ оплаты';
$_['kassa_any_geo_zone'] = 'Любая зона';

$_['kassa_debug_log_label'] = 'Debug log';
$_['kassa_debug_log_description'] = 'Подробное логгирование процесса проведения оплаты';
$_['kassa_debug_log_off'] = 'Выключить';
$_['kassa_debug_log_on'] = 'Включить';
$_['kassa_view_logs'] = 'Просмотр журнала сообщений';

$_['kassa_sort_order_label'] = 'Сортировка';
$_['kassa_sort_order_description'] = '';

$_['kassa_invoice_label'] = 'Выставление счетов по электронной почте';

$_['kassa_invoice_heading_label'] = 'Шаблон письма';
$_['kassa_invoice_subject_label'] = 'Тема';
$_['kassa_invoice_subject_default'] = 'Оплата заказа %order_id%';
$_['kassa_invoice_subject_description'] = 'Номер заказа (значение %order_id%) подставится автоматически';
$_['kassa_invoice_message_label'] = 'Дополнительный текст';
$_['kassa_invoice_message_description'] = 'Этот текст появится в письме после суммы и кнопки "Заплатить": напишите здесь важную для покупателя информацию или оставьте поле пустым';

$_['kassa_invoice_logo_label'] = 'Добавить к письму логотип магазина';

$_['kassa_invoices_kassa_disabled'] = 'Этот функционал доступен только для оплаты через Яндекс.Кассу';
$_['kassa_invoices_disabled'] = 'Этот функционал отключен в настройках модуля Яндекс.Кассы';
$_['kassa_invoices_invalid_order_id'] = 'Идентификатор заказа не был передан или не валиден';
$_['kassa_invoices_order_not_exists'] = 'Указанный заказ не найден';

$_['kassa_refund_status_pending_label'] = 'В ожидании';
$_['kassa_refund_status_succeeded_label'] = 'Проведён';
$_['kassa_refund_status_canceled_label'] = 'Отменён';

$_['kassa_breadcrumbs_payments'] = 'Список платежей через модуль Кассы';
$_['kassa_payments_page_title'] = 'Список платежей через модуль Кассы';
$_['kassa_payments_update_button'] = 'Обновить список';
$_['kassa_payments_capture_button'] = 'Провести все платежи';
$_['kassa_payment_list_label'] = 'Список платежей через модуль Кассы';
$_['kassa_payment_list_link'] = 'Открыть список';

$_['kassa_payment_description_label'] = 'Описание платежа';
$_['kassa_payment_description_description'] = 'Это описание транзакции, которое пользователь увидит при оплате, а вы — в личном кабинете Яндекс.Кассы. Например, «Оплата заказа №72». Чтобы в описание подставлялся номер заказа (как в примере), поставьте на его месте %order_id% (Оплата заказа %order_id%). Ограничение для описания — 128 символов.';
$_['kassa_default_payment_description'] = 'Оплата заказа №%order_id%';

$_['kassa_tab_header'] = 'Яндекс.Касса';
$_['wallet_tab_header'] = 'Яндекс.Деньги';
$_['billing_tab_header'] = 'Яндекс.Платёжка';
$_['metrika_tab_header'] = 'Яндекс.Метрика';
$_['market_tab_header'] = 'Яндекс.Маркет';
$_['orders_tab_header'] = 'Заказы в Маркете';

$_['wallet_page_title'] = 'Настройки Яндекс.Денег';
$_['wallet_header_description'] = '';
$_['wallet_version_string'] = 'Версия модуля';

$_['wallet_enable_label'] = 'Включить прием платежей в кошелек на Яндексе';
$_['wallet_account_id_label'] = 'Номер кошелька';
$_['wallet_account_id_description'] = '';
$_['wallet_account_id_error_required'] = 'Укажите номер кошелька';

$_['wallet_application_id_label'] = 'ID приложения';
$_['wallet_application_id_description'] = '';
$_['wallet_application_id_error_required'] = 'Укажите идентификатор приложения';

$_['wallet_password_label'] = 'Секретное слово';
$_['wallet_password_description'] = 'ID и секретное слово вы получите после регистрации приложения на сайте Яндекс.Денег';
$_['wallet_password_error_required'] = 'Укажите секретное слово';

$_['wallet_display_name_label'] = 'Название платежного сервиса';
$_['wallet_display_name_description'] = 'Это название увидит пользователь';
$_['wallet_default_display_name'] = 'Яндекс.Деньги (банковские карты, кошелек)';

$_['wallet_notification_url_label'] = '';
$_['wallet_notification_url_description'] = 'Скопируйте эту ссылку в поле Redirect URI на <a href=\'https://sp-money.yandex.ru/myservices/new.xml\' target=\'_blank\'>странице регистрации приложения</a>';

$_['wallet_success_order_status_label'] = 'Статус заказа после оплаты';
$_['wallet_success_order_status_description'] = '';

$_['wallet_minimum_payment_amount_label'] = 'Минимальная сумма заказа';
$_['wallet_minimum_payment_amount_description'] = 'Сумма заказа при которой можно провести платёж с помощью Яндекс.Кассы';

$_['wallet_before_redirect_label'] = 'Когда пользователь переходит к оплате';
$_['wallet_create_order_label'] = 'Создать неоплаченный заказ в панели управления';
$_['wallet_clear_cart_label'] = 'Удалить товары из корзины';

$_['wallet_geo_zone_label'] = 'Регион отображения';
$_['wallet_geo_zone_description'] = 'Геозона в которой будет отображаться способ оплаты';
$_['wallet_any_geo_zone'] = 'Любая зона';

$_['wallet_sort_order_label'] = 'Сортировка';
$_['wallet_sort_order_description'] = '';

$_['billing_page_title'] = 'Настройки Яндекс.Платёжки';
$_['billing_header_description'] = '';
$_['billing_version_string'] = 'Версия модуля';

$_['billing_enable_label'] = 'Включить прием платежей через Яндекс.Платёжку';
$_['billing_form_id_label'] = 'ID формы';
$_['billing_form_id_description'] = '';
$_['billing_form_id_error_required'] = 'Укажите идентификатор формы';

$_['billing_purpose_label'] = 'Назначение платежа';
$_['billing_purpose_description'] = 'Назначение будет в платежном поручении: напишите в нем всё, что поможет отличить заказ, который оплатили через Платежку';
$_['billing_default_purpose'] = 'Номер заказа %order_id% Оплата через Яндекс.Платежку';

$_['billing_display_name_label'] = 'Название платежного сервиса';
$_['billing_display_name_description'] = 'Это название увидит пользователь';
$_['billing_default_display_name'] = 'Яндекс.Платежка (банковские карты, кошелек)';

$_['billing_success_order_status_label'] = 'Статус заказа';
$_['billing_success_order_status_description'] = 'Статус должен показать, что результат платежа неизвестен: заплатил клиент или нет, вы можете узнать только из уведомления на электронной почте или в своем банке';

$_['billing_minimum_payment_amount_label'] = 'Минимальная сумма заказа';
$_['billing_minimum_payment_amount_description'] = 'Сумма заказа при которой можно провести платёж с помощью Яндекс.Кассы';

$_['billing_geo_zone_label'] = 'Регион отображения';
$_['billing_geo_zone_description'] = 'Геозона в которой будет отображаться способ оплаты';
$_['billing_any_geo_zone'] = 'Любая зона';

$_['billing_sort_order_label'] = 'Сортировка';
$_['billing_sort_order_description'] = '';

// market_
$_['market_set']          = 'Условия выгрузки';
$_['market_set_1']          = 'Выгружать только товары в наличии';
//$_['market_set_2']          = 'Использовать поле доставки в домашнем регионе (добавляет в прайс-лист элемент local_delivery_cost)';
$_['market_set_3']          = 'Выгружать все комбинации товара (цвета, размеры и т. д.)';
$_['market_set_4']          = 'Выгружать все характеристики товара';
$_['market_set_5']          = 'Показывать размеры товара в упаковке (dimensions)';
$_['market_set_6']          = 'Выгружать все валюты (если нет, выгрузится только валюта по умолчанию)';
$_['market_set_7']          = 'Товар можно купить в розничном магазине';
$_['market_set_8']          = 'Возможность доставки товара';
$_['market_set_9']          = 'Возможен самовывоз';
$_['market_lnk_yml']          = 'Ссылка для выгрузки товаров на Маркет';
$_['market_cat']          = 'Категории и товары для выгрузки';
$_['market_out']          = 'Выгружать';
$_['market_out_all']          = 'Все товары';
$_['market_out_sel']          = 'Выбранные категории';

$_['market_dostup']          = 'Срок доставки в пункт самовывоза';
$_['market_dostup_1']          = 'До 2 дней для всех товаров';
$_['market_dostup_2']          = 'До 2 дней для товаров в наличии';
$_['market_dostup_3']          = 'Определяется индивидуально';
$_['market_dostup_4']          = 'Самовывоза нет';

$_['market_s_name']          = 'Название магазина';
$_['market_d_cost']          = 'Стоимость доставки в домашнем регионе';
$_['market_d_days']          = 'Срок доставки в домашнем регионе';
$_['market_sv_all']          = 'Свернуть всё';
$_['market_rv_all']          = 'Развернуть всё';
$_['market_ch_all']          = 'Отметить всё';
$_['market_unch_all']          = 'Убрать все отметки';
$_['market_sv']          = 'Сохранить';
$_['market_gen']          = 'Генерировать';
$_['market_prostoy']          = 'Упрощенный YML';
$_['text_success']       = 'Настройки сохранены';
$_['market_color_option'] = 'Параметры цвета';
$_['market_size_option']  = 'Параметры размеров';
$_['market_size_unit'] 	 = '';//'Шкала или единица измерения размеров:<br/><span class="help">Размер должен быть числом, кроме размеров международной шкалы: XS, S, M, L, и т.д., для бюстгальтеров: AA, A, B, C и т.д.</span>';


// pokupki
$_['pokupki_gtoken']       = 'Получить токен (нажмите после сохранения настроек)';
$_['pokupki_stoken']       = 'Авторизационный токен из настроек Маркета';
$_['pokupki_yapi']       = 'URL партнёрского API Яндекс.Маркет';
$_['pokupki_number']       = 'Номер кампании на Маркете';
$_['pokupki_login']       = 'Логин пользователя в системе Яндекс.Маркет';
$_['pokupki_pw']       = 'Пароль <a target="_blank" href="https://oauth.yandex.ru">приложения OAuth</a>';
$_['pokupki_idapp']       = 'ID <a target="_blank" href="https://oauth.yandex.ru">приложения OAuth</a>';
$_['pokupki_token']       = 'Авторизационный токен';
$_['pokupki_idpickup']       = 'Идентификатор пункта самовывоза';
$_['pokupki_method']       = 'Разрешённые методы оплаты';
$_['pokupki_sapi']       = 'URL API';
$_['pokupki_set_1']       = 'Предоплата - Оплата при оформлении';
//$_['pokupki_set_2']       = 'Предоплата - Напрямую магазину (только для Украины)';
$_['pokupki_set_3']       = 'Постоплата - Наличный расчёт при получении товара';
$_['pokupki_set_4']       = 'Постоплата - Оплата банковской картой при получении заказа';

$_['pokupki_text_status_pickup']       = 'Заказ доставлен в пункт самовывоза';
$_['pokupki_text_status_cancelled']       = 'Заказ отменен';
$_['pokupki_text_status_delivery']       = 'Заказ передан в доставку';
$_['pokupki_text_status_processing']       = 'Заказ находится в обработке';
$_['pokupki_text_status_unpaid']       = 'Заказ оформлен, но еще не оплачен';
$_['pokupki_text_status_delivered']       = 'Заказ получен покупателем';
$_['pokupki_text_status']       = 'Статусы для отправки в Яндекс.Маркет';
$_['pokupki_callback']       = 'Callback URL для <a target="_blank" href="https://oauth.yandex.ru">приложения OAuth</a>';

$_['pokupki_sv']       = 'Сохранить';

$_['pokupki_upw']       = 'Пароль пользователя в системе Яндекс.Маркет';

// metrika
$_['metrika_gtoken']       = 'Получить токен для доступа к Яндекс.Метрика';
$_['metrika_number']       = 'Номер счётчика';
$_['metrika_sv']       = 'Сохранить';
$_['metrika_pw']       = 'Пароль приложения';
$_['metrika_uname']       = 'Логин пользователя в системе Яндекс.Метрика';
$_['metrika_upw']       = 'Пароль пользователя в системе Яндекс.Метрика';
$_['metrika_idapp']       = 'ID Приложения';
$_['metrika_o2auth']       = 'Токен OAuth';
$_['metrika_set']       = 'Настройки';
$_['metrika_set_1']       = 'Вебвизор';
$_['metrika_set_2']       = 'Карта кликов';
$_['metrika_set_3']       = 'Внешние ссылки, загрузки файлов и отчёт по кнопке "Поделиться"';
$_['metrika_set_4']       = 'Точный показатель отказов';
$_['metrika_set_5']       = 'Отслеживание хеша в адресной строке браузера';
$_['metrika_celi']       = 'Собирать статистику по следующим целям:';
$_['celi_cart']       = 'Корзина(Посетитель кликнул "Добавить в корзину")';
$_['celi_order']       = 'Заказ(Посетитель оформил заказ)';
$_['metrika_callback']       = 'Ссылка для приложения';
// market
$_['p2p_sv']       = 'Сохранить';
$_['p2p_text_connect']          = "Для работы с модулем нужно <a href='https://money.yandex.ru/new' target='_blank'>открыть кошелек</a> на Яндексе и <a href='https://sp-money.yandex.ru/myservices/new.xml' target='_blank'>зарегистрировать приложение</a> на сайте Яндекс.Денег								";
$_['p2p_text_enable']          = "Включить прием платежей в кошелек на Яндексе";
$_['p2p_text_url_help']          = "Скопируйте эту ссылку в поле Redirect URI на <a href='https://sp-money.yandex.ru/myservices/new.xml' target='_blank'>странице регистрации приложения</a>";
$_['p2p_text_setting_head']          = "Настройки приема платежей";
$_['p2p_text_account']          = "Номер кошелька";
$_['p2p_text_appId']          = "Id приложения";
$_['p2p_text_appWord']          = "Секретное слово";
$_['p2p_text_app_help']          = "ID и секретное слово вы получите после регистрации приложения на сайте Яндекс.Денег";
$_['p2p_text_extra_head']          = "Дополнительные настройки для администратора";
$_['p2p_text_debug']          = "Запись отладочной информации";
$_['p2p_text_off']          = "Отключена";
$_['p2p_text_on']          = "Включена";
$_['p2p_text_debug_help']          = "Настройку нужно будет поменять, только если попросят специалисты Яндекс.Денег";
$_['p2p_text_status']          = "Статус заказа после оплаты";
// MWS
$_['lbl_mws_main']       = 'Настройка взаимодействия по протоколу MWS (<a target="_blank" href="https://tech.yandex.ru/money/doc/payment-solution/payment-management/payment-management-about-docpage/">Merchant Web Services</a>)';
$_['txt_mws_main']       = 'Для работы с MWS необходимо получить в Яндекс.Деньгах специальный сертификат и загрузить его в приложении.';
$_['lbl_mws_crt']       = 'Сертификат';
$_['lbl_mws_connect']       = 'Как получить сертификат';
$_['txt_mws_connect']       = 'Скачайте <a href="%s">готовый запрос на сертификат</a> (файл в формате .csr).';
$_['lbl_mws_doc']       = 'Данные для заполнения заявки';
$_['txt_mws_doc']       = 'Скачайте <a target="_blank"  href="https://money.yandex.ru/i/html-letters/SSL_Cert_Form.doc">заявку на сертификат</a>. Ее нужно заполнить, распечатать, поставить подпись и печать. Внизу страницы — таблица с данными для заявки, просто скопируйте их. Отправьте файл запроса вместе со сканом готовой заявки менеджеру Яндекс.Денег на <a href="mailto:merchants@yamoney.ru">merchants@yamoney.ru</a>.';
$_['txt_mws_cer']       = 'Загрузите сертификат, который пришлет вам менеджер, наверху этой страницы.';

$_['lbl_mws_cn']       = 'CN';
$_['lbl_mws_email']       = 'Email техника';

$_['tab_mws_before']       =	'Скопируйте эти данные в таблицу. Остальные строчки заполните самостоятельно.';
$_['tab_row_sign']       =	'Электронная подпись на сертификат';
$_['tab_row_cause']       =	'Причина запроса';
$_['tab_row_primary']       =	'Первоначальный';


$_['btn_mws_gen']       = 'Сформировать запрос на сертификат (CSR)';
$_['btn_mws_csr']       = 'Скачать запрос на сертификат (CSR)';
$_['btn_mws_doc']       = 'Скачать для заполнения';
$_['btn_mws_crt']       = 'Обзор';
$_['btn_mws_crt_load']  = 'Загрузить';

$_['success_mws_alert'] = "<p class='alert alert-success'>Модуль настроен для работы с платежами и возвратами. Сертификат загружен.</p>
    <p>Посмотреть информацию о платеже или сделать возврат можно в <a href='%s' target='blank'>списке заказов</a></p>
    <p><a href='%s' id='mws_csr_gen'>Сбросить настройки</a></p>";
$_['lbl_mws_alert']     = "Все настройки для работы с MWS будут стерты. Сертификат нужно будет запросить повторно. Вы действительно хотите сбросить настройки MWS?";
$_['ext_mws_openssl']   = 'Отсутствует расширения openssl';
$_['err_mws_kassa']     = 'Отключен модуль Яндекс.Кассы';
$_['err_mws_shopid']    = 'Отсутствует идентификатор магазина (shopId)';


// Error
$_['error_permission'] = 'У Вас нет прав для управления этим модулем!';
$_['active_on']        = 'Включено';
$_['active_off']       = 'Выключено';
$_['active']           = 'Активность';

//Updater
$_['updater_tab_header'] = 'Обновление модуля';
$_['updater_success_message'] = 'Версия модуля %s была успешно загружена и установлена';
$_['updater_error_unpack_failed'] = 'Не удалось распаковать загруженный архив %s, подробную информацию о произошедшей ошибке можно найти в <a href="">логах модуля</a>';
$_['updater_error_backup_create_failed'] = 'Не удалось создать бэкап установленной версии модуля, подробную информацию о произошедшей ошибке можно найти в <a href="%s">логах модуля</a>';
$_['updater_error_archive_load'] = 'Не удалось загрузить архив с новой версией, подробную информацию о произошедшей ошибке можно найти в <a href="%s">логах модуля</a>';
$_['updater_restore_backup_message'] = 'Версия модуля %s была успешно восстановлена из бэкапа %s';
$_['updater_error_restore_backup'] = 'Не удалось восстановить данные из бэкапа, подробную информацию о произошедшей ошибке можно найти в <a href="%s">логах модуля</a>';
$_['updater_backup_deleted_message'] = 'Бэкап %s был успешно удалён';
$_['updater_error_delete_backup'] = 'Не удалось удалить бэкап %s, подробную информацию о произошедшей ошибке можно найти в <a href="%s">логах модуля</a>';
$_['updater_error_create_directory'] = 'Не удалось создать директорию %s';
$_['updater_error_load'] = 'Не удалось загрузить архив с обновлением';
$_['updater_header_text'] = 'Здесь будут появляться новые версии модуля — с новыми возможностями или с исправленными ошибками. Чтобы установить новую версию модуля, нажмите кнопку «Обновить».';
$_['updater_about_title'] = 'О модуле';
$_['updater_current_version'] = 'Установленная версия модуля';
$_['updater_last_version'] = 'Последняя доступная версия модуля';
$_['updater_last_check_date'] = 'Дата проверки наличия новой версии';
$_['updater_check_updates'] = 'Проверить наличие обновлений';
$_['updater_history_title'] = 'История изменений:';
$_['updater_update'] = 'Обновить модуль';
$_['updater_error_load'] = 'Не удалось загрузить архив с обновлением';
$_['updater_last_version_installed'] = 'Установлена последняя версия модуля.';
$_['updater_backups_title'] = 'Резервные копии';
$_['updater_module_version'] = 'Версия модуля';
$_['updater_date_create'] = 'Дата создания';
$_['updater_file_name'] = 'Имя файла';
$_['updater_file_size'] = 'Размер файла';
$_['updater_restore'] = 'Восстановить';
$_['updater_delete'] = 'Удалить';
$_['updater_delete_message'] = 'Вы действительно хотите удалить бэкап модуля версии ';
$_['updater_restore_message'] = 'Вы действительно хотите восстановить модуль из бэкапа версии';
