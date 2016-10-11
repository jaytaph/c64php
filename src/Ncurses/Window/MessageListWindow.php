<?php

namespace Ncurses\Window;

use Ncurses\Window;

class MessageListWindow extends AbstractWindow {

    /** @var AccountManager */
    protected $accountManager;

    function __construct(AccountManager $accountManager)
    {
        $this->accountManager = $accountManager;
    }

    function getTitle() {
        return "Messages";
    }

    function getName() {
        return "message_list";
    }

    function init()
    {
        $max = $this->windowManager->getSize();
        $rows = $max['rows'];
        $cols = $max['cols'];

        $rightCols = $max['cols'] / 4;
        if ($rightCols > 30) { $rightCols = 30; }
        $leftCols = $cols - $rightCols;

        $this->window = new Window($this->windowManager->getNcurses(), $leftCols, $rows / 2, $rightCols, 0);
    }

    function drawBody()
    {
        $size = $this->window->getSize();
        $cols = $this->windowManager->generateColumns(array('70%', '30%', '20'));

        $activeFolder = $this->accountManager->getActiveFolder();
        $messages = $activeFolder->getMessages(true);

        // Figure out where to start
        $messages = array_slice($messages, 0, $size['rows'] - 2);

        $y = 1;
        foreach ($messages as $message) {
            /* @var Message $message */
            $color = ($activeFolder->isActiveMessage($message)) ? 'default-selected' : 'default';

            $this->window
                ->clearLine($y, $color)
                ->drawString($cols[0], $y, ' ' . $message->getSubject(), $color)
                ->drawString($cols[1], $y, ' ' . $message->getFrom()->getName()." <".$message->getFrom()->getEmail().">", $color)
                ->drawString($cols[2], $y, ' ' . $message->getDate()->format('d-M-Y H:i'), $color);
            $y++;
        }
    }

}
