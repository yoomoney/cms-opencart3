<?php

//Remove old modificator
$this->load->model("extension/modification");
$old_mod = $this->model_extension_modification->getModificationByCode("ycms2");
if (isset($old_mod['modification_id'])) {
    $this->model_extension_modification->deleteModification($old_mod['modification_id']);
}

if (!file_exists(DIR_UPLOAD . $this->request->post['path']."/upload/admin/controller/extension")) {
    //Архив не для 2.3.х
    if (version_compare(VERSION, "2.3.0", '>=')) {
        throw new Exception("Архив создавался не для этой версии Opencart. Загрузите правильную версию по адресу https://github.com/yandex-money/yandex-money-cms-opencart2/blob/master/ycms.oc23x.ocmod.zip?raw=true");
    }
} else {
    //Архив для 2.3.х
    if (version_compare(VERSION, "2.2.0", '>=') && !version_compare(VERSION, "2.3.0", '>=')) {
        throw new Exception("Архив создавался не для этой версии Opencart. Загрузите правильную версию по адресу https://github.com/yandex-money/yandex-money-cms-opencart2/blob/master/ycms.oc22x.ocmod.zip?raw=true");
    } elseif (version_compare(VERSION, "2.2.0", '<')) {
        throw new Exception("Архив создавался не для этой версии Opencart. Загрузите правильную версию по адресу https://github.com/yandex-money/yandex-money-cms-opencart2/blob/master/ycms.oc20x21x.ocmod.zip?raw=true");
    }
}
?>