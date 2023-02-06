<?php 

namespace Osky;

use DateTime;
use DateTimeZone;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Osky\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Author: Chidume Nnamdi <kurtwanger40@gmail.com>
 */
class SearchCommand extends Command
{
    
    public function configure()
    {
        $this -> setName('reddit:search')
            -> setDescription('Search reddit posts')
            -> setHelp('This command allows you to search reddit post...')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        
        $output -> writeln([
            '<fg=red>Reddit Search v.0.1.0</>',
            '<fg=red>=====================</>',
            '',
        ]);

        $default = 'webdev';
        $outputQSubreddit = new Question('Please enter the name of the subreddit (default: '. $default .'):', $default);
        $subreddit = strtolower($helper->ask($input, $output, $outputQSubreddit));


        $default = 'php';
        $outputQTerms = new Question('Please enter the search terms (default: '. $default .'):', $default);
        $terms = strtolower($helper->ask($input, $output, $outputQTerms));

        $url = 'https://reddit.com/r/' . $subreddit . '/new';

        $output -> writeln(['','Searching for "' . $terms .'" in ' . $url . ' ...','']);

        
        $client = HttpClient::create();
        $response = $client->request('GET', $url . '.json', [
            'headers' => [
                'User-Agent' => 'CLI App OSKY',
            ],
            'query' => [
                'limit' => 100,
            ],
        ]);
        $content = $response->toArray(false);

        if (200 !== $response->getStatusCode()) {
            $output -> writeln('<error>'.json_encode($content).'</error>');
            return Command::FAILURE;
        }

        $posts = $content['data']['children'];

        $filteredPosts = [];

        foreach($posts as $post){
            if($post['data']['selftext'] == ''){
                continue;
            }
            $title = $post['data']['title'];
            if(strlen($title) > 30){
                $title = substr($title,0,30) . '...' ;
            }
            if (str_contains($post['data']['title'], $terms)){
                $excerpt = $this->excerptAndToArray($post['data']['title'], $terms);
                $filteredPosts[] = array($this->dateFormat($post['data']['created_utc']), $title, $post['data']['url'], $excerpt);
            }else{
                if (str_contains($post['data']['selftext'], $terms)){
                    $excerpt = $this->excerptAndToArray($post['data']['selftext'], $terms);
                    $filteredPosts[] = array($this->dateFormat($post['data']['created_utc']), $title, $post['data']['url'], $excerpt);
                }
            }
        }

        if(count($filteredPosts) == 0){
            $output -> writeln('<error>No posts found</error>');
            return Command::SUCCESS;
        }
        
        $keys = array_column($filteredPosts, 1);
        array_multisort($keys, SORT_ASC, $filteredPosts);

        $table = new Table($output);
        $table
            ->setHeaders(['Date', 'Title', 'URL', 'Excerpt'])
            ->setRows($filteredPosts)
        ;
        $table->render();

        return Command::SUCCESS;
    }

    function excerptAndToArray($text, $term){
        $parts = explode($term,$text,2);
        if(strlen($parts[0]) > 20){
            $parts[0] = '...' . substr($parts[0],-20,20);
        }
        if(strlen($parts[1]) > 20){
            $parts[1] = substr($parts[1],0,20) . '...' ;
        }
        return $parts[0] . '<options=bold,underscore>' . $term . '</>' . $parts[1];
    }

    function dateFormat($date){
        $dt = new DateTime('@'.$date);
        $dt->setTimeZone(new DateTimeZone('Asia/Makassar'));
        return $dt->format('Y/m/d H:i:s');
    }
}