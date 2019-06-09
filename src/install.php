<?php

//Remove old modificator
$this->load->model("extension/modification");
$old_mod = $this->model_extension_modification->getModificationByCode("ycms.2.0");
if (isset($old_mod['modification_id'])) {
    $this->model_extension_modification->deleteModification($old_mod['modification_id']);
}

if (version_compare(VERSION, '3.0') < 0) {
    throw new Exception('
        Архив создавался не для этой версии Opencart. Загрузите правильную версию по адресу '
        . 'https://github.com/yandex-money/yandex-money-ycms-opencart3'
    );
}
