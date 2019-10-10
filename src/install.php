<?php

//Remove old modificator
$this->load->model("setting/modification");
$old_mod = $this->model_setting_modification->getModificationByCode("ycms.2.0");
if (isset($old_mod['modification_id'])) {
    $this->model_setting_modification->deleteModification($old_mod['modification_id']);
}

if (version_compare(VERSION, '3.0') < 0) {
    throw new Exception('
        Архив создавался не для этой версии Opencart. Загрузите правильную версию по адресу '
        . 'https://github.com/yandex-money/yandex-money-ycms-v2-opencart'
    );
}
