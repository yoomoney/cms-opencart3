<?php

namespace YooMoney\Updater\ProjectStructure;

class ProjectStructureWriter
{
    public function writeToString(RootDirectory $directory)
    {
        $result = array();
        foreach ($directory->getDirectoryEntries() as $entry) {
            $result[] = 'd:' . $entry->getProjectPath() . ':' . $entry->getRelativePath();
        }
        foreach ($directory->getFileEntries() as $entry) {
            $result[] = 'f:' . $entry->getProjectPath() . ':' . $entry->getRelativePath();
        }
        return implode("\n", $result);
    }
}
