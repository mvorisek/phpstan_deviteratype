<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace _HumbugBox221ad6f1b81f\Symfony\Component\Console\Style;

use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Exception\InvalidArgumentException;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Exception\RuntimeException;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Formatter\OutputFormatter;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Helper;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\ProgressBar;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Table;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\TableCell;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\TableSeparator;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Input\InputInterface;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Output\BufferedOutput;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Output\OutputInterface;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\ChoiceQuestion;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\ConfirmationQuestion;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\Question;
use _HumbugBox221ad6f1b81f\Symfony\Component\Console\Terminal;
/**
 * Output decorator helpers for the Symfony Style Guide.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class SymfonyStyle extends \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Style\OutputStyle
{
    const MAX_LINE_LENGTH = 120;
    private $input;
    private $questionHelper;
    private $progressBar;
    private $lineLength;
    private $bufferedOutput;
    public function __construct(\_HumbugBox221ad6f1b81f\Symfony\Component\Console\Input\InputInterface $input, \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->input = $input;
        $this->bufferedOutput = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Output\BufferedOutput($output->getVerbosity(), \false, clone $output->getFormatter());
        // Windows cmd wraps lines as soon as the terminal width is reached, whether there are following chars or not.
        $width = (new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Terminal())->getWidth() ?: self::MAX_LINE_LENGTH;
        $this->lineLength = \min($width - (int) (\DIRECTORY_SEPARATOR === '\\'), self::MAX_LINE_LENGTH);
        parent::__construct($output);
    }
    /**
     * Formats a message as a block of text.
     *
     * @param string|array $messages The message to write in the block
     * @param string|null  $type     The block type (added in [] on first line)
     * @param string|null  $style    The style to apply to the whole block
     * @param string       $prefix   The prefix for the block
     * @param bool         $padding  Whether to add vertical padding
     * @param bool         $escape   Whether to escape the message
     */
    public function block($messages, $type = null, $style = null, $prefix = ' ', $padding = \false, $escape = \true)
    {
        $messages = \is_array($messages) ? \array_values($messages) : [$messages];
        $this->autoPrependBlock();
        $this->writeln($this->createBlock($messages, $type, $style, $prefix, $padding, $escape));
        $this->newLine();
    }
    /**
     * {@inheritdoc}
     */
    public function title($message)
    {
        $this->autoPrependBlock();
        $this->writeln([\sprintf('<comment>%s</>', \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Formatter\OutputFormatter::escapeTrailingBackslash($message)), \sprintf('<comment>%s</>', \str_repeat('=', \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Helper::strlenWithoutDecoration($this->getFormatter(), $message)))]);
        $this->newLine();
    }
    /**
     * {@inheritdoc}
     */
    public function section($message)
    {
        $this->autoPrependBlock();
        $this->writeln([\sprintf('<comment>%s</>', \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Formatter\OutputFormatter::escapeTrailingBackslash($message)), \sprintf('<comment>%s</>', \str_repeat('-', \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Helper::strlenWithoutDecoration($this->getFormatter(), $message)))]);
        $this->newLine();
    }
    /**
     * {@inheritdoc}
     */
    public function listing(array $elements)
    {
        $this->autoPrependText();
        $elements = \array_map(function ($element) {
            return \sprintf(' * %s', $element);
        }, $elements);
        $this->writeln($elements);
        $this->newLine();
    }
    /**
     * {@inheritdoc}
     */
    public function text($message)
    {
        $this->autoPrependText();
        $messages = \is_array($message) ? \array_values($message) : [$message];
        foreach ($messages as $message) {
            $this->writeln(\sprintf(' %s', $message));
        }
    }
    /**
     * Formats a command comment.
     *
     * @param string|array $message
     */
    public function comment($message)
    {
        $this->block($message, null, null, '<fg=default;bg=default> // </>', \false, \false);
    }
    /**
     * {@inheritdoc}
     */
    public function success($message)
    {
        $this->block($message, 'OK', 'fg=black;bg=green', ' ', \true);
    }
    /**
     * {@inheritdoc}
     */
    public function error($message)
    {
        $this->block($message, 'ERROR', 'fg=white;bg=red', ' ', \true);
    }
    /**
     * {@inheritdoc}
     */
    public function warning($message)
    {
        $this->block($message, 'WARNING', 'fg=black;bg=yellow', ' ', \true);
    }
    /**
     * {@inheritdoc}
     */
    public function note($message)
    {
        $this->block($message, 'NOTE', 'fg=yellow', ' ! ');
    }
    /**
     * {@inheritdoc}
     */
    public function caution($message)
    {
        $this->block($message, 'CAUTION', 'fg=white;bg=red', ' ! ', \true);
    }
    /**
     * {@inheritdoc}
     */
    public function table(array $headers, array $rows)
    {
        $style = clone \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Table::getStyleDefinition('symfony-style-guide');
        $style->setCellHeaderFormat('<info>%s</info>');
        $table = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Table($this);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle($style);
        $table->render();
        $this->newLine();
    }
    /**
     * Formats a horizontal table.
     */
    public function horizontalTable(array $headers, array $rows)
    {
        $style = clone \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Table::getStyleDefinition('symfony-style-guide');
        $style->setCellHeaderFormat('<info>%s</info>');
        $table = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Table($this);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle($style);
        $table->setHorizontal(\true);
        $table->render();
        $this->newLine();
    }
    /**
     * Formats a list of key/value horizontally.
     *
     * Each row can be one of:
     * * 'A title'
     * * ['key' => 'value']
     * * new TableSeparator()
     *
     * @param string|array|TableSeparator ...$list
     */
    public function definitionList(...$list)
    {
        $style = clone \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Table::getStyleDefinition('symfony-style-guide');
        $style->setCellHeaderFormat('<info>%s</info>');
        $table = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Table($this);
        $headers = [];
        $row = [];
        foreach ($list as $value) {
            if ($value instanceof \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\TableSeparator) {
                $headers[] = $value;
                $row[] = $value;
                continue;
            }
            if (\is_string($value)) {
                $headers[] = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\TableCell($value, ['colspan' => 2]);
                $row[] = null;
                continue;
            }
            if (!\is_array($value)) {
                throw new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Exception\InvalidArgumentException('Value should be an array, string, or an instance of TableSeparator.');
            }
            $headers[] = \key($value);
            $row[] = \current($value);
        }
        $table->setHeaders($headers);
        $table->setRows([$row]);
        $table->setHorizontal();
        $table->setStyle($style);
        $table->render();
        $this->newLine();
    }
    /**
     * {@inheritdoc}
     */
    public function ask($question, $default = null, $validator = null)
    {
        $question = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\Question($question, $default);
        $question->setValidator($validator);
        return $this->askQuestion($question);
    }
    /**
     * {@inheritdoc}
     */
    public function askHidden($question, $validator = null)
    {
        $question = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\Question($question);
        $question->setHidden(\true);
        $question->setValidator($validator);
        return $this->askQuestion($question);
    }
    /**
     * {@inheritdoc}
     */
    public function confirm($question, $default = \true)
    {
        return $this->askQuestion(new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\ConfirmationQuestion($question, $default));
    }
    /**
     * {@inheritdoc}
     */
    public function choice($question, array $choices, $default = null)
    {
        if (null !== $default) {
            $values = \array_flip($choices);
            $default = isset($values[$default]) ? $values[$default] : $default;
        }
        return $this->askQuestion(new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\ChoiceQuestion($question, $choices, $default));
    }
    /**
     * {@inheritdoc}
     */
    public function progressStart($max = 0)
    {
        $this->progressBar = $this->createProgressBar($max);
        $this->progressBar->start();
    }
    /**
     * {@inheritdoc}
     */
    public function progressAdvance($step = 1)
    {
        $this->getProgressBar()->advance($step);
    }
    /**
     * {@inheritdoc}
     */
    public function progressFinish()
    {
        $this->getProgressBar()->finish();
        $this->newLine(2);
        $this->progressBar = null;
    }
    /**
     * {@inheritdoc}
     */
    public function createProgressBar($max = 0)
    {
        $progressBar = parent::createProgressBar($max);
        if ('\\' !== \DIRECTORY_SEPARATOR || 'Hyper' === \getenv('TERM_PROGRAM')) {
            $progressBar->setEmptyBarCharacter('░');
            // light shade character \u2591
            $progressBar->setProgressCharacter('');
            $progressBar->setBarCharacter('▓');
            // dark shade character \u2593
        }
        return $progressBar;
    }
    /**
     * @return mixed
     */
    public function askQuestion(\_HumbugBox221ad6f1b81f\Symfony\Component\Console\Question\Question $question)
    {
        if ($this->input->isInteractive()) {
            $this->autoPrependBlock();
        }
        if (!$this->questionHelper) {
            $this->questionHelper = new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\SymfonyQuestionHelper();
        }
        $answer = $this->questionHelper->ask($this->input, $this, $question);
        if ($this->input->isInteractive()) {
            $this->newLine();
            $this->bufferedOutput->write("\n");
        }
        return $answer;
    }
    /**
     * {@inheritdoc}
     */
    public function writeln($messages, $type = self::OUTPUT_NORMAL)
    {
        if (!\is_iterable($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            parent::writeln($message, $type);
            $this->writeBuffer($message, \true, $type);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = \false, $type = self::OUTPUT_NORMAL)
    {
        if (!\is_iterable($messages)) {
            $messages = [$messages];
        }
        foreach ($messages as $message) {
            parent::write($message, $newline, $type);
            $this->writeBuffer($message, $newline, $type);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function newLine($count = 1)
    {
        parent::newLine($count);
        $this->bufferedOutput->write(\str_repeat("\n", $count));
    }
    /**
     * Returns a new instance which makes use of stderr if available.
     *
     * @return self
     */
    public function getErrorStyle()
    {
        return new self($this->input, $this->getErrorOutput());
    }
    private function getProgressBar() : \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\ProgressBar
    {
        if (!$this->progressBar) {
            throw new \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Exception\RuntimeException('The ProgressBar is not started.');
        }
        return $this->progressBar;
    }
    private function autoPrependBlock() : void
    {
        $chars = \substr(\str_replace(\PHP_EOL, "\n", $this->bufferedOutput->fetch()), -2);
        if (!isset($chars[0])) {
            $this->newLine();
            //empty history, so we should start with a new line.
            return;
        }
        //Prepend new line for each non LF chars (This means no blank line was output before)
        $this->newLine(2 - \substr_count($chars, "\n"));
    }
    private function autoPrependText() : void
    {
        $fetched = $this->bufferedOutput->fetch();
        //Prepend new line if last char isn't EOL:
        if ("\n" !== \substr($fetched, -1)) {
            $this->newLine();
        }
    }
    private function writeBuffer(string $message, bool $newLine, int $type) : void
    {
        // We need to know if the two last chars are PHP_EOL
        // Preserve the last 4 chars inserted (PHP_EOL on windows is two chars) in the history buffer
        $this->bufferedOutput->write(\substr($message, -4), $newLine, $type);
    }
    private function createBlock(iterable $messages, string $type = null, string $style = null, string $prefix = ' ', bool $padding = \false, bool $escape = \false) : array
    {
        $indentLength = 0;
        $prefixLength = \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Helper::strlenWithoutDecoration($this->getFormatter(), $prefix);
        $lines = [];
        if (null !== $type) {
            $type = \sprintf('[%s] ', $type);
            $indentLength = \strlen($type);
            $lineIndentation = \str_repeat(' ', $indentLength);
        }
        // wrap and add newlines for each element
        foreach ($messages as $key => $message) {
            if ($escape) {
                $message = \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Formatter\OutputFormatter::escape($message);
            }
            $lines = \array_merge($lines, \explode(\PHP_EOL, \wordwrap($message, $this->lineLength - $prefixLength - $indentLength, \PHP_EOL, \true)));
            if (\count($messages) > 1 && $key < \count($messages) - 1) {
                $lines[] = '';
            }
        }
        $firstLineIndex = 0;
        if ($padding && $this->isDecorated()) {
            $firstLineIndex = 1;
            \array_unshift($lines, '');
            $lines[] = '';
        }
        foreach ($lines as $i => &$line) {
            if (null !== $type) {
                $line = $firstLineIndex === $i ? $type . $line : $lineIndentation . $line;
            }
            $line = $prefix . $line;
            $line .= \str_repeat(' ', $this->lineLength - \_HumbugBox221ad6f1b81f\Symfony\Component\Console\Helper\Helper::strlenWithoutDecoration($this->getFormatter(), $line));
            if ($style) {
                $line = \sprintf('<%s>%s</>', $style, $line);
            }
        }
        return $lines;
    }
}
