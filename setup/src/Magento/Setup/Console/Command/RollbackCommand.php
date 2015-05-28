<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Setup\Console\Command;

use Magento\Framework\Filesystem\Driver\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Setup\Model\ConsoleLogger;
use Symfony\Component\Console\Input\InputOption;
use Magento\Setup\Model\ObjectManagerProvider;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\MaintenanceMode;
use Magento\Setup\Model\BackupRollback;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Command to rollback code and DB
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RollbackCommand extends AbstractSetupCommand
{
    /**
     * Name of input arguments or options
     */
    const INPUT_KEY_CODE_ROLLBACK = 'code';

    /**
     * Object Manager
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var MaintenanceMode
     */
    private $maintenanceMode;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var File
     */
    private $file;

    /**
     * Constructor
     *
     * @param ObjectManagerProvider $objectManagerProvider
     * @param MaintenanceMode $maintenanceMode
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        ObjectManagerProvider $objectManagerProvider,
        MaintenanceMode $maintenanceMode,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->objectManager = $objectManagerProvider->get();
        $this->maintenanceMode = $maintenanceMode;
        $this->directoryList = $directoryList;
        $this->file = $file;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $inputOptions = [
            new InputOption(
                self::INPUT_KEY_CODE_ROLLBACK,
                'c',
                InputOption::VALUE_REQUIRED,
                'Rollback code. Value is the backup filename without path.'
            ),
        ];
        $this->setName('setup:rollback')
            ->setDescription('Rollbacks Magento Application code base or database')
            ->setDefinition($inputOptions);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('<info>Enabling maintenance mode</info>');
            $this->maintenanceMode->set(true);
            if ($input->getOption(self::INPUT_KEY_CODE_ROLLBACK)) {
                $backupRollback = new BackupRollback(
                    $this->objectManager,
                    new ConsoleLogger($output),
                    $this->directoryList,
                    $this->file
                );
                $backupRollback->codeRollback($input->getOption(self::INPUT_KEY_CODE_ROLLBACK));
            }
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        } finally {
            $output->writeln('<info>Disabling maintenance mode</info>');
            $this->maintenanceMode->set(false);
        }
    }
}
