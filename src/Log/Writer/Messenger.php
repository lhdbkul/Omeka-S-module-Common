<?php declare(strict_types=1);

namespace Common\Log\Writer;

use Common\Stdlib\PsrMessage;
use Laminas\Log\Logger;
use Laminas\Log\Writer\AbstractWriter;
use Omeka\Mvc\Controller\Plugin\Messenger as MessengerPlugin;

/**
 * Republish the log of a job to the messenger of the current page.
 *
 * A job dispatched with the synchronous strategy runs inside the web request,
 * so its result is useful right away, but it is only written to the job log.
 * This writer displays it to the user like any other page message.
 *
 * The eight log levels are mapped to the four messenger types, so the mapping
 * is lossy on purpose: anything more severe than a warning is an error, and
 * the informational levels are notices. Debug is skipped: it is meant for the
 * log, not for the user.
 *
 * @see \Omeka\Log\Writer\Job
 */
class Messenger extends AbstractWriter
{
    /**
     * Log priority (rfc 5424) to messenger type.
     */
    const PRIORITIES_TO_TYPES = [
        Logger::EMERG => MessengerPlugin::ERROR,
        Logger::ALERT => MessengerPlugin::ERROR,
        Logger::CRIT => MessengerPlugin::ERROR,
        Logger::ERR => MessengerPlugin::ERROR,
        Logger::WARN => MessengerPlugin::WARNING,
        Logger::NOTICE => MessengerPlugin::NOTICE,
        Logger::INFO => MessengerPlugin::NOTICE,
    ];

    /**
     * Avoid to fill the page with a job logging thousands of rows.
     */
    const DEFAULT_LIMIT = 100;

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Messenger
     */
    protected $messenger;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $count = 0;

    public function __construct(MessengerPlugin $messenger, ?int $limit = null)
    {
        $this->messenger = $messenger;
        $this->limit = $limit ?? self::DEFAULT_LIMIT;
    }

    /**
     * Add the log event to the messenger.
     *
     * The message is not formatted, but rebuilt as a PsrMessage with its
     * context, so the placeholders are kept and the message is translated when
     * it is displayed.
     */
    protected function doWrite(array $event): void
    {
        $priority = $event['priority'] ?? Logger::NOTICE;
        if (!isset(self::PRIORITIES_TO_TYPES[$priority])
            || $this->count >= $this->limit
        ) {
            return;
        }
        ++$this->count;

        $context = $event['extra']['context'] ?? $event['extra'] ?? [];
        $message = is_array($context) && count($context)
            ? new PsrMessage((string) $event['message'], $context)
            : (string) $event['message'];

        $this->messenger->add(self::PRIORITIES_TO_TYPES[$priority], $message);

        if ($this->count === $this->limit) {
            $this->messenger->add(
                MessengerPlugin::NOTICE,
                new PsrMessage(
                    'Only the first {count} messages are displayed: see the job log for the other ones.', // @translate
                    ['count' => $this->limit]
                )
            );
        }
    }
}
