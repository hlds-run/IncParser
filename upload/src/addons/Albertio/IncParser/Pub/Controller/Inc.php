<?php

namespace Albertio\IncParser\Pub\Controller;

use XF\Pub\Controller\AbstractController;

class Inc extends AbstractController
{
    public function actionIndex()
    {
        $repo = $this->repository("Albertio\IncParser:IncRepository");

        $file = $this->filter("file", "str");
        $itemId = $this->filter("item", "str");
        $query = $this->filter("q", "str");

        $files = $repo->searchItems($repo->getFileListWithItems(), $query);

        $items = [];
        $selectedItem = null;

        if ($file !== "") {
            $items = $repo->getFileItems($file);
            
            if ($itemId !== "") {
                foreach ($items as $it) {
                    if ($it["id"] === $itemId) {
                        $selectedItem = $it;
                        break;
                    }
                }
            }
        }

        return $this->view("Albertio\IncParser:Viewer", "inc_parser_viewer", [
            "files" => $files,
            "activeFile" => $file,
            "selectedItem" => $selectedItem,
            "query" => $query,
        ]);
    }
}