<?php

namespace SCM\MDTGeneratorBundle\Command;


use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineCrudCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use SCM\MDTGeneratorBundle\Generator\DoctrineRESTGenerator;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Sensio\Bundle\GeneratorBundle\Command\AutoComplete\EntitiesAutoCompleter;
use SCM\MDTGeneratorBundle\Manipulator\RoutingManipulator;

/**
 * Generates a REST api for a Doctrine entity.
 *
 */
class GenerateDoctrineRESTCommand extends GenerateDoctrineCrudCommand
{
    /**
     * @var
     */
    private $formGenerator;

    /**
     * @see Command
     */
    protected function configure()
    {
        $this->setDefinition(
            array(
                new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The entity class name to initialize (shortcut notation)'),
                new InputOption('route-prefix', '', InputOption::VALUE_REQUIRED, 'The route prefix'),
                new InputOption('overwrite', '', InputOption::VALUE_NONE, 'Do not stop the generation if rest api controller already exist, thus overwriting all generated files'),
                new InputOption('resource', '', InputOption::VALUE_NONE, 'The object will return with the resource name'),
                new InputOption('document', '', InputOption::VALUE_NONE, 'Use NelmioApiDocBundle to document the controller'),
            )
        )
            ->setDescription('Generates a REST api based on a Doctrine entity')
            ->setHelp(
                <<<EOT
                The <info>mdt:generate:rest</info> command generates a REST api based on a Doctrine entity.

<info>php app/console mdt:generate:rest --entity=AcmeBlogBundle:Post --api-namespace=blog --route-prefix=post_admin</info>

Every generated file is based on a template. There are default templates but they can be overriden by placing custom templates in one of the following locations, by order of priority:

<info>BUNDLE_PATH/Resources/SensioGeneratorBundle/skeleton/rest
APP_PATH/Resources/SensioGeneratorBundle/skeleton/rest</info>

And

<info>__bundle_path__/Resources/SensioGeneratorBundle/skeleton/form
__project_root__/app/Resources/SensioGeneratorBundle/skeleton/form</info>

You can check https://github.com/sensio/SensioGeneratorBundle/tree/master/Resources/skeleton
in order to know the file structure of the skeleton
EOT
            )
            ->setName('mdt:generate:rest')
            ->setAliases(array('generate:mdt:rest'));
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);
            if (!$questionHelper->ask($input, $output, $question)) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        $entity = Validators::validateEntityName($input->getOption('entity'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        $prefix         = $this->getRoutePrefix($input, $entity);

        $forceOverwrite = $input->getOption('overwrite');

        $questionHelper->writeSection($output, 'REST api generation');

        $entityClass = $this->getContainer()->get('doctrine')->getAliasNamespace($bundle) . '\\' . $entity;
        $metadata    = $this->getEntityMetadata($entityClass);
        $bundle      = $this->getContainer()->get('kernel')->getBundle($bundle);
        $resource    = $input->getOption('resource');
        $document    = $input->getOption('document');

        $generator = $this->getGenerator($bundle);
        $generator->generate($bundle, $entity, $metadata[0], $prefix, $forceOverwrite, $resource, $document);

        $output->writeln('Generating the REST api code: <info>OK</info>');

        $errors = array();

        // form
        $this->generateForm($bundle, $entity, $metadata);
        $output->writeln('Generating the Form code: <info>OK</info>');

        $questionHelper->writeGeneratorSummary($output, $errors);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Doctrine2 REST api generator');

        // namespace
        $output->writeln(
            array(
                '',
                'This command helps you generate a REST api controller.',
                '',
                'First, you need to give the entity for which you want to generate a REST api.',
                'You can give an entity that does not exist yet and the wizard will help',
                'you defining it.',
                '',
                'You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>.',
                '',
            )
        );

        $question = new Question($questionHelper->getQuestion('The Entity shortcut name', $input->getOption('entity')), $input->getOption('entity'));
        $question->setValidator(array('Sensio\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName'));
        $autocompleter = new EntitiesAutoCompleter($this->getContainer()->get('doctrine')->getManager());
        $autocompleteEntities = $autocompleter->getSuggestions();
        $question->setAutocompleterValues($autocompleteEntities);
        $entity = $questionHelper->ask($input, $output, $question);

        $input->setOption('entity', $entity);
        list($bundle, $entity) = $this->parseShortcutNotation($entity);

        // route prefix
        $prefix = 'api/v1/'.strtolower($entity);
        $output->writeln(
            array(
                '',
                'Determine the routes prefix (all the API routes will be "mounted" under this',
                'prefix: /prefix/, /prefix/posts, ...).',
                '',
            )
        );

        $prefix = $questionHelper->ask($input, $output, new Question($questionHelper->getQuestion('Routes prefix', '/' . $prefix), '/' . $prefix));
        $input->setOption('route-prefix', $prefix);

        // summary
        $output->writeln(
            array(
                '',
                $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
                '',
                sprintf("You are going to generate a REST api controller for \"<info>%s:%s</info>\"", $bundle, $entity),
                '',
            )
        );
    }


    /**
     * @param null $bundle
     * @return DoctrineRESTGenerator
     */
    protected function createGenerator($bundle = null)
    {
        return new DoctrineRESTGenerator($this->getContainer()->get('filesystem'));
    }

    /**
     * @param BundleInterface $bundle
     * @return array
     */
    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {

        $reflClass = new \ReflectionClass(get_class($this));

        $skeletonDirs = parent::getSkeletonDirs($bundle);
        $skeletonDirs[] = dirname($reflClass->getFileName()) . '/../Resources/skeleton';
        $skeletonDirs[] = dirname($reflClass->getFileName()) . '/../Resources';

        return $skeletonDirs;
    }

    protected  function fromCamelCase($input) {

        $input = explode("Bundle",$input)[0];
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('-', $ret);
    }

}
