<?php

declare(strict_types=1);

namespace Freema\ReactAdminApiBundle\Command;

use Freema\ReactAdminApiBundle\OpenApi\OpenApiSchemaBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'react-admin-api:dump-openapi',
    description: 'Dump OpenAPI 3.1 spec for every configured react-admin-api resource',
)]
final class DumpOpenApiCommand extends Command
{
    public function __construct(private readonly OpenApiSchemaBuilder $schemaBuilder)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format: yaml or json', 'yaml')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write to this file instead of stdout', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = strtolower((string) $input->getOption('format'));
        if (!in_array($format, ['yaml', 'json'], true)) {
            $output->writeln('<error>Unsupported format. Use yaml or json.</error>');

            return Command::INVALID;
        }

        $spec = $this->schemaBuilder->build();
        $serialized = $format === 'json'
            ? json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n"
            : Yaml::dump($spec, 6, 2, Yaml::DUMP_OBJECT_AS_MAP);

        $destination = $input->getOption('output');
        if (is_string($destination) && $destination !== '') {
            $dir = dirname($destination);
            if (!is_dir($dir) && !mkdir($dir, 0o755, true) && !is_dir($dir)) {
                $output->writeln(sprintf('<error>Could not create directory %s</error>', $dir));

                return Command::FAILURE;
            }
            file_put_contents($destination, $serialized);
            $output->writeln(sprintf('<info>OpenAPI spec written to %s</info>', $destination));

            return Command::SUCCESS;
        }

        $output->write($serialized);

        return Command::SUCCESS;
    }
}
