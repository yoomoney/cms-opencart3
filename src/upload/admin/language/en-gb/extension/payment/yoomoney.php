<?php

$_['module_title'] = 'YooMoney';
$_['heading_title'] = $_['module_title'];
$_['text_yoomoney'] = '<a target="_blank" href="https://yookassa.ru"><img src="../image/catalog/payment/yoomoney/yoomoney_logo.png" alt="YooMoney from YooKassa" /></a>';
$_['kassa_header_description'] = 'Работая с модулем, вы автоматически соглашаетесь с <a href="https://yoomoney.ru/doc.xml?id=527132">условиями его использования</a>.';
$_['kassa_version_string'] = 'Версия модуля';

$_['kassa_breadcrumbs_extension'] = 'Расширения';
$_['kassa_breadcrumbs_home'] = 'Главная';
$_['kassa_breadcrumbs_logs'] = 'Журнал сообщений';
$_['kassa_text_success'] = 'Success';
$_['kassa_page_title'] = 'Настройки ЮKassa';
$_['kassa_breadcrumbs_heading_title'] = 'Журнал сообщений платежного модуля ЮMoney';
$_['kassa_test_mode_description'] = 'Вы включили тестовый режим приема платежей. Проверьте, как проходит оплата, и напишите менеджеру ЮKassa. Он выдаст рабочие shopId и Секретный ключ. <a href="https://yookassa.ru/docs/support/payments/onboarding/integration" target="_blank">Инструкция</a>';

$_['kassa_enable_label'] = 'Включить приём платежей через ЮKassa';

$_['kassa_shop_id_label'] = 'shopId';
$_['kassa_shop_id_description'] = 'Скопируйте shopId из личного кабинета ЮKassa';
$_['kassa_shop_id_error_required'] = 'Необходимо указать shopId из личного кабинета ЮKassa';

$_['kassa_password_label'] = 'Секретный ключ';
$_['kassa_password_description'] = 'Выпустите и активируйте секретный ключ в <a href="https://yookassa.ru/my" target="_blank">личном кабинете ЮKassa</a>. Потом скопируйте его сюда.';
$_['kassa_password_error_required'] = 'Необходимо указать секретный ключ из личного кабинета ЮKassa';
$_['kassa_error_invalid_credentials'] = 'Проверьте shopId и Секретный ключ — где-то есть ошибка. А лучше скопируйте их прямо из <a href="https://yookassa.ru/my" target="_blank">личного кабинета ЮKassa</a>';

$_['kassa_payment_mode_label'] = 'Выбор способа оплаты';
$_['kassa_payment_mode_kassa_label'] = 'На стороне ЮKassa';
$_['kassa_use_installments_button_label'] = 'Add the Installments payment method to checkout page';
$_['kassa_add_installments_block_label'] = 'Add the information block about Installments to product descriptions';
$_['kassa_payment_mode_shop_label'] = 'На стороне магазина';

$_['kassa_payment_method_bank_card'] = 'Банковские карты';
$_['kassa_payment_method_sberbank'] = 'Сбербанк Онлайн';
$_['kassa_payment_method_cash'] = 'Наличные через терминалы';
$_['kassa_payment_method_qiwi'] = 'QIWI Wallet';
$_['kassa_payment_method_alfabank'] = 'Альфа-Клик';
$_['kassa_payment_method_webmoney'] = 'Webmoney';
$_['kassa_payment_method_yoo_money'] = 'YooMoney';
$_['kassa_payment_method_mobile'] = 'Баланс мобильного';
$_['kassa_payment_method_installments'] = 'Installments';
$_['kassa_payment_method_tinkoff_bank'] = 'Tinkoff online banking';
$_['kassa_payment_method_widget'] = 'Payment widget from YooKassa (cards, Apple Pay and Google Play)';

$_['kassa_payment_method_error_required'] = 'Пожалуйста, выберите хотя бы один способ из списка';

$_['kassa_display_name_label'] = 'Название платежного сервиса';
$_['kassa_display_name_description'] = 'Это название увидит пользователь';
$_['kassa_default_display_name'] = 'ЮKassa (банковские карты, электронные деньги и другое)';

$_['kassa_currency']                     = 'Валюта платежа в ЮKassa';
$_['kassa_currency_convert']             = 'Конвертировать сумму из текущей валюты магазина';
$_['kassa_currency_help']                = 'Валюты в ЮKassa и в магазине должны совпадать';
$_['kassa_currency_convert_help']        = 'Используется значение из списка валют магазина. Если валюты нет в списке – курс ЦБ РФ.';

$_['kassa_send_receipt_label']           = 'Отправлять в ЮKassa данные для чеков (54-ФЗ)';
$_['kassa_send_receipt_tax_rate_title']  = 'НДС';
$_['kassa_second_receipt_header']        = 'Второй чек';
$_['kassa_second_receipt_enable']        = 'Включен';
$_['kassa_second_receipt_disable']       = 'Отключен';
$_['kassa_second_receipt_description']   = 'Два чека нужно формировать, если покупатель вносит предоплату и потом получает товар или услугу. Первый чек — когда деньги поступают вам на счёт, второй — при отгрузке товаров или выполнении услуг.';
$_['kassa_second_receipt_enable_label']  = 'Формировать второй чек при переходе заказа в статус';
$_['kassa_second_receipt_help_info']     = 'Если в заказе будут позиции с признаками «Полная предоплата» — второй чек отправится автоматически, когда заказ перейдёт в выбранный статус.';
$_['kassa_second_receipt_history_info']  = 'Отправлен второй чек. Сумма %s рублей.';
$_['kassa_tax_system_default_label']     = 'Система налогообложения по умолчанию';
$_['kassa_tax_system_default_description'] = 'Выберите систему налогообложения по умолчанию. Параметр необходим, только если у вас несколько систем налогообложения, в остальных случаях не передается.';
$_['kassa_tax_system_1_label']           = 'Общая система налогообложения';
$_['kassa_tax_system_2_label']           = 'Упрощенная (УСН, доходы)';
$_['kassa_tax_system_3_label']           = 'Упрощенная (УСН, доходы минус расходы)';
$_['kassa_tax_system_4_label']           = 'Единый налог на вмененный доход (ЕНВД)';
$_['kassa_tax_system_5_label']           = 'Единый сельскохозяйственный налог (ЕСН)';
$_['kassa_tax_system_6_label']           = 'Патентная система налогообложения';
$_['kassa_tax_rate_default_label']       = 'Ставка по умолчанию';
$_['kassa_tax_rate_default_description'] = 'Ставка по умолчанию будет в чеке, если в карточке товара не указана другая ставка.';
$_['kassa_tax_rate_1_label']             = 'Без НДС';
$_['kassa_tax_rate_2_label']             = '0%';
$_['kassa_tax_rate_3_label']             = '10%';
$_['kassa_tax_rate_4_label']             = '20%';
$_['kassa_tax_rate_5_label']             = 'Расчетная ставка 10/110';
$_['kassa_tax_rate_6_label']             = 'Расчетная ставка 20/120';
$_['kassa_tax_rate_table_caption']       = 'Сопоставьте ставки';
$_['kassa_shop_tax_rate_header']         = 'Ставка в вашем магазине';
$_['kassa_kassa_tax_rate_header']        = 'Ставка для чека в налоговую';

$_['kassa_notification_url_label'] = 'Адрес для уведомлений';
$_['kassa_notification_url_description'] = 'Этот адрес понадобится, только если его попросят специалисты ЮKassa';

$_['kassa_before_redirect_label'] = 'Когда пользователь переходит к оплате';
$_['kassa_create_order_label'] = 'Создать неоплаченный заказ в панели управления';
$_['kassa_clear_cart_label'] = 'Удалить товары из корзины';

$_['kassa_success_order_status_label'] = 'Статус заказа после оплаты';
$_['kassa_success_order_status_description'] = '';

$_['kassa_minimum_payment_amount_label'] = 'Минимальная сумма заказа';
$_['kassa_minimum_payment_amount_description'] = 'Сумма заказа при которой можно провести платёж с помощью ЮKassa';

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

$_['kassa_invoices_kassa_disabled'] = 'Этот функционал доступен только для оплаты через ЮKassa';
$_['kassa_invoices_disabled'] = 'Этот функционал отключен в настройках модуля ЮKassa';
$_['kassa_invoices_invalid_order_id'] = 'Идентификатор заказа не был передан или не валиден';
$_['kassa_invoices_order_not_exists'] = 'Указанный заказ не найден';

$_['kassa_refund_status_pending_label'] = 'В ожидании';
$_['kassa_refund_status_succeeded_label'] = 'Проведён';
$_['kassa_refund_status_canceled_label'] = 'Отменён';

$_['kassa_breadcrumbs_payments'] = 'Список платежей через модуль ЮKassa';
$_['kassa_payments_page_title'] = 'Список платежей через модуль ЮKassa';
$_['kassa_payments_update_button'] = 'Обновить список';
$_['kassa_payments_capture_button'] = 'Провести все платежи';
$_['kassa_payment_list_label'] = 'Список платежей через модуль ЮKassa';
$_['kassa_payment_list_link'] = 'Открыть список';

$_['kassa_tab_header'] = 'ЮKassa';
$_['wallet_tab_header'] = 'ЮMoney';

$_['kassa_payment_description_label'] = 'Transaction data';
$_['kassa_payment_description_description'] = 'Full description of the transaction that the user will see during the checkout process. You can find it in your YooKassa Merchant Profile. For example, "Payment for order No. 72 by user@yoomoney.ru".
Limitations: no more than 128 symbols';
$_['kassa_default_payment_description'] = 'Payment for order No. %order_id%';

$_['wallet_page_title'] = 'Настройки ЮMoney';
$_['wallet_header_description'] = 'Для работы с модулем нужно открыть <a href=\'https://yoomoney.ru/new\' target=\'_blank\'>кошелек</a> ЮMoney.';
$_['wallet_version_string'] = 'Версия модуля';

$_['wallet_enable_label'] = 'Включить прием платежей в кошелек ЮMoney';
$_['wallet_account_id_label'] = 'Номер кошелька';
$_['wallet_account_id_description'] = '';
$_['wallet_account_id_error_required'] = 'Укажите номер кошелька';

$_['wallet_password_label'] = 'Секретное слово';
$_['wallet_password_description']    = 'Секретное слово нужно скопировать <a href=\'https://yoomoney.ru/transfer/myservices/http-notification\' target=\'_blank\'>со страницы настройки уведомлений</a> на сайте ЮMoney';
$_['wallet_password_error_required'] = 'Укажите секретное слово';

$_['wallet_display_name_label'] = 'Название платежного сервиса';
$_['wallet_display_name_description'] = 'Это название увидит пользователь';
$_['wallet_default_display_name'] = 'ЮMoney (банковские карты, кошелек)';

$_['wallet_notification_url_label'] = 'RedirectURL';
$_['wallet_notification_url_description'] = 'Скопируйте эту ссылку в поле Redirect URI <a href=\'https://yoomoney.ru/transfer/myservices/http-notification\' target=\'_blank\'>со страницы настройки уведомлений</a> на сайте ЮMoney';

$_['wallet_success_order_status_label'] = 'Статус заказа после оплаты';
$_['wallet_success_order_status_description'] = '';

$_['wallet_minimum_payment_amount_label'] = 'Минимальная сумма заказа';
$_['wallet_minimum_payment_amount_description'] = 'Сумма заказа при которой можно провести платёж';

$_['wallet_before_redirect_label'] = 'Когда пользователь переходит к оплате';
$_['wallet_create_order_label'] = 'Создать неоплаченный заказ в панели управления';
$_['wallet_clear_cart_label'] = 'Удалить товары из корзины';

$_['wallet_geo_zone_label'] = 'Регион отображения';
$_['wallet_geo_zone_description'] = 'Геозона в которой будет отображаться способ оплаты';
$_['wallet_any_geo_zone'] = 'Любая зона';

$_['wallet_sort_order_label'] = 'Сортировка';
$_['wallet_sort_order_description'] = '';

$_['ok'] = 'OK';
$_['cancel'] = 'Cancel';
$_['delete'] = 'Удалить';
$_['text_success']       = 'Settings saved';

// Error
$_['error_permission']   = 'You do not have the right to manage the module!';
$_['error_install_widget'] = 'Чтобы покупатели могли заплатить вам через Apple Pay, <a href="https://yookassa.ru/docs/merchant.ru.yandex.kassa">скачайте файл apple-developer-merchantid-domain-association</a> и добавьте его в папку ./well-known на вашем сайте. Если не знаете, как это сделать, обратитесь к администратору сайта или в поддержку хостинга. Не забудьте также подключить оплату через Apple Pay <a href="https://yookassa.ru/my/payment-methods/settings#applePay">в личном кабинете ЮKassa</a>. <a href="https://yookassa.ru/developers/payment-forms/widget#apple-pay-configuration">Почитать о подключении Apple Pay в документации ЮKassa</a>';

$_['active_on']          = 'Activated';
$_['active_off']          = 'Disabled';
$_['active']          = 'Activity';

//Updater
$_['updater_tab_header'] = 'Module update';
$_['updater_success_message'] = 'Module version %s successfully downloaded and installed';
$_['updater_error_unpack_failed'] = 'Unable to extract archive %s. More about the error in <a href="">module\'s logs</a>';
$_['updater_error_backup_create_failed'] = 'Unable to create a backup copy of the installed module version. More about the error in <a href="%s">module\'s logs</a>';
$_['updater_error_archive_load'] = 'Unable to load the latest module version archive. More about the error in <a href="%s">module\'s logs</a>';
$_['updater_restore_backup_message'] = 'Module version %s successfully restored from backup %s';
$_['updater_error_restore_backup'] = 'Unable to restore data from the backup. More about the error in <a href="%s">module\'s logs</a>';
$_['updater_backup_deleted_message'] = 'Backup %s successfully deleted';
$_['updater_error_delete_backup'] = 'Unable to delete backup %s. More about the error in the <a href="%s">module\'s logs</a>';
$_['updater_error_create_directory'] = 'Unable to create directory %s';
$_['updater_error_load'] = 'Unable to load the archive with the update';
$_['updater_header_text'] = 'New module versions with added features and fixed errors will appear here. Click the Update button to install the latest module version.';
$_['updater_about_title'] = 'About the module';
$_['updater_current_version'] = 'Current module version';
$_['updater_last_version'] = 'Latest available module version';
$_['updater_last_check_date'] = 'Date of the last check for updates';
$_['updater_check_updates'] = 'Check for updates';
$_['updater_history_title'] = 'Changelog:';
$_['updater_update'] = 'Update module';
$_['updater_error_load'] = 'Unable to load the archive with the update';
$_['updater_last_version_installed'] = 'You have the latest module version installed.';
$_['updater_backups_title'] = 'Backups';
$_['updater_module_version'] = 'Module version';
$_['updater_date_create'] = 'Creation date';
$_['updater_file_name'] = 'File name';
$_['updater_file_size'] = 'File size';
$_['updater_restore'] = 'Restore';
$_['updater_delete'] = 'Delete';
$_['updater_delete_message'] = 'Do you really want to delete the backup copy of this module version ';
$_['updater_restore_message'] = 'Do you really want to restore the module from the backup copy of this version';
$_['text_repay'] = 'Pay';
$_['text_payment_on_hold']            = 'Payment on hold';


//Подтверждение платежа
$_['kassa_hold_setting_label']         = 'Включить отложенную оплату';
$_['kassa_hold_setting_description']   = 'Если опция включена, платежи с карт проходят в 2 этапа: у клиента сумма замораживается, и вам вручную нужно подтвердить её списание – через панель администратора';
$_['kassa_statuses_description_label'] = 'Какой статус присваивать заказу, если он:';
$_['kassa_hold_order_status_label']    = 'ожидает подтверждения';
$_['kassa_cancel_order_status_label']  = 'отменён';

$_['captures_title']                  = 'Подтверждение платежа';
$_['captures_expires_date']           = 'Подтвердить до';
$_['captures_new']                    = 'Подтверждение платежа';
$_['captures_payment_data']           = 'Данные платежа';
$_['captures_payment_id']             = 'Номер транзакции в ЮKassa';
$_['captures_order_id']               = 'Номер заказа';
$_['captures_payment_method']         = 'Способ оплаты';
$_['captures_payment_sum']            = 'Сумма платежа';
$_['captures_capture_data']           = '';
$_['captures_capture_sum']            = 'Сумма подтверждения';
$_['captures_capture_create']         = 'Подтвердить платеж';
$_['cancel_payment_button']           = 'Отменить платеж';
$_['capture_payment_success_message'] = 'Платеж подтвержден успешно';
$_['capture_payment_fail_message']    = 'Ошибка подтверждения платежа';
$_['cancel_payment_success_message']  = 'Платеж отменен успешно';
$_['cancel_payment_fail_message']     = 'Ошибка отмены платежа';

$_['column_product']  = 'Наименование товара';
$_['column_quantity'] = 'Количество товара';
$_['column_price']    = 'Цена товара';
$_['column_total']    = 'Итого';

$_['nps_text'] = '';

$_['b2b_sberbank_label']             = 'Enable payments via Sberbank Business Online';
$_['b2b_sberbank_on_label']          = 'If you enable this option, you will be able to accept online payments from legal entities. Learn more at the <a href="https://yookassa.ru/en/">YooKassa website</a>.';
$_['b2b_sberbank_template_label']    = 'Template for payment details';
$_['b2b_sberbank_vat_default_label'] = 'Default VAT rate';
$_['b2b_sberbank_template_help']     = 'These payment details will be shown in the payment order.';
$_['b2b_sberbank_vat_default_help']  = 'This rate will be sent to Sberbank Business Online if there\'s no other rate indicated in the payment description.';
$_['b2b_sberbank_vat_label']         = 'Compare the VAT rates in your store with the rates for Sberbank Business Online';
$_['b2b_sberbank_vat_cms_label']     = 'VAT rate at your store';
$_['b2b_sberbank_vat_sbbol_label']   = 'VAT rate for Sberbank Business Online';
$_['b2b_tax_rate_untaxed_label']     = 'Without VAT';
$_['b2b_tax_rate_7_label']           = '7%';
$_['b2b_tax_rate_10_label']          = '10%';
$_['b2b_tax_rate_18_label']          = '18%';
$_['b2b_tax_rate_20_label']          = '20%';
$_['b2b_sberbank_tax_message']       = 'There is a restriction for payments via Sberbank Business Online: one receipt can only contain products with the same VAT rate. If the client wants to pay for products with different VAT rates at the same time, we will show him the message explaining that it\'s not possible.';

$_['kassa_payment_mode_default_label']          = 'Признак способа расчета';
$_['kassa_payment_subject_default_label']       = 'Признак предмета расчета';
$_['kassa_payment_subject_default_description'] = 'Признаки предмета расчёта и способа расчёта берутся из атрибутов товара payment_mode и payment_subject. Их значения можно задать отдельно в карточке товара, если это потребуется. <a href="https://yookassa.ru/developers/54fz/basics#ffd-1-05">Подробнее.</a><br>Для товаров, у которых значения этих атрибутов не заданы, будем применять значения по умолчанию:';

$_['kassa_delivery_payment_mode_default_label']    = 'Признак способа расчета для доставки';
$_['kassa_delivery_payment_subject_default_label'] = 'Признак предмета расчета для доставки';
