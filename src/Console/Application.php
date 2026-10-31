<?php

namespace Fwhy\Blast\Console;

use Fwhy\Blast\Builder;
use Fwhy\Blast\Exception\ConfigException;
use Fwhy\Blast\Exception\DirectoryException;

class Application
{
    private string $name = 'Blast';
    private string $version = '0.1.0';

    /**
     * Run CLI application with given arguments
     *
     * @param array<int, string> $argv
     * @return int Exit code
     * @throws ConfigException
     * @throws DirectoryException
     */
    public function run(array $argv): int
    {
        $args = array_slice($argv, 1);
        $command = $args[0] ?? 'build';

        if (in_array('-v', $args, true) || in_array('--version', $args, true)) {
            echo "{$this->name} {$this->version}\n";
            return 0;
        }

        // If no arguments or command is help/global help
        if (count($args) === 0 || $command === '-h' || $command === '--help' || $command === 'help') {
            $this->printHelp();
            return 0;
        }

        return match ($command) {
            'build' => $this->runBuild($args),
            'dev' => $this->runDev($args),
            'help' => $this->runHelp($args),
            default => $this->handleUnknownCommand($command),
        };
    }

    /**
     * Execute build command
     *
     * @param array<int, string> $args
     * @return int
     * @throws ConfigException|DirectoryException
     * @throws \Exception
     */
    private function runBuild(array $args): int
    {
        if (in_array('-h', $args, true) || in_array('--help', $args, true)) {
            echo "Usage: blast build [options]\n\n";
            echo "Build the static site.\n\n";
            echo "Options:\n";
            echo "  -h, --help    Display this help message\n";
            return 0;
        }

        $builder = new Builder();
        $builder->loadConfig();
        $builder->createDirectories();
        $builder->loadHooks();
        $builder->buildPages();
        $builder->render();
        $builder->output();

        return 0;
    }

    /**
     * Execute dev command
     *
     * @param array<int, string> $args
     * @return int
     */
    private function runDev(array $args): int
    {
        if (in_array('-h', $args, true) || in_array('--help', $args, true)) {
            echo "Usage: blast dev [options]\n\n";
            echo "Start the development server.\n\n";
            echo "Options:\n";
            echo "  -p, --port <port>    Port to listen on (default: 3000)\n";
            echo "  -h, --help           Display this help message\n";
            return 0;
        }

        $port = '3000';
        for ($i = 0; $i < count($args); $i++) {
            if ($args[$i] === '-p' || $args[$i] === '--port') {
                if (isset($args[$i + 1])) {
                    $port = $args[$i + 1];
                    $i++;
                }
            } elseif (str_starts_with($args[$i], '-p')) {
                $port = substr($args[$i], 2);
            } elseif (str_starts_with($args[$i], '--port=')) {
                $port = substr($args[$i], 7);
            }
        }

        $devServer = dirname(__DIR__) . '/DevServer.php';
        echo "Starting Blast development server on http://localhost:{$port} ...\n";
        system("php -S 0.0.0.0:{$port} " . escapeshellarg($devServer));

        return 0;
    }

    /**
     * Display help
     *
     * @param array<int, string> $args
     * @return int
     */
    private function runHelp(array $args): int
    {
        $this->printHelp();
        return 0;
    }

    /**
     * Handle unknown command
     *
     * @param string $command
     * @return int
     */
    private function handleUnknownCommand(string $command): int
    {
        fwrite(STDERR, "Unknown command: {$command}\n\n");
        $this->printHelp();
        return 1;
    }

    /**
     * Print general help
     *
     * @return void
     */
    private function printHelp(): void
    {
        echo "{$this->name} static site generator ({$this->version})\n\n";
        echo "Usage:\n";
        echo "  blast <command> [options]\n\n";
        echo "Available commands:\n";
        echo "  build       Build static files\n";
        echo "  dev         Start development server\n";
        echo "  help        Display help for a command\n\n";
        echo "Global Options:\n";
        echo "  -h, --help       Display help\n";
        echo "  -v, --version    Display version\n";
    }
}
