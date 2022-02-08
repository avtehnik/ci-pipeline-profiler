<?php


class GItlab
{

    private $baseUrl = 'https://gitlab.com/api/v4';
    private $pat;

    private $projectId;
    private $vars = [];

    /**
     * @param $pat
     * @param $projectId
     */
    public function __construct($pat, $projectId)
    {
        $this->pat = $pat;
        $this->projectId = $projectId;

        $this->setVar('id', $this->projectId);
    }


    private function setPipelineId($id)
    {
        $this->setVar('pipeline_id', $id);
    }

    private function setShaId($id)
    {
        $this->setVar('sha', $id);
    }


    private function setPage($id)
    {
        $this->setVar('page', $id);
    }

    public function getPipelines()
    {


        foreach ([0,1,3] as $page ){
            $pipelines = $this->curlGet('/projects/:id/pipelines', ['scope' => 'finished', 'page'=>$page]);
            foreach ($pipelines as &$pipeline) {
                $commit = $this->getCommit($pipeline->sha);
                $pipeline = $this->getPipeline($pipeline->id);
                echo 'title:  ' . $commit->title;
                echo 'id:  ' . $pipeline->id . PHP_EOL;
                echo 'sha: ' . $pipeline->sha . PHP_EOL;
                echo 'ref: ' . $pipeline->ref . PHP_EOL;
                $pipeline->jobs = $this->getJobs($pipeline->id);
                $pipeline->title = $commit->title;
                echo PHP_EOL;
//            $this->getJobs($pipeline->id);
            }
        }
        file_put_contents('data.json', json_encode($pipelines));
    }

    private function setVar($name, $value)
    {
        $this->vars[':' . $name] = $value;
    }

    public function getJobs($id)
    {
        $this->setPipelineId($id);
        $jobs = $this->curlGet('/projects/:id/pipelines/:pipeline_id/jobs', ['scope' => 'success']);

        return $jobs;

        foreach ($jobs as $job) {
            echo $job->duration . '  ' . $job->name . PHP_EOL;
        }

//        print_r($jobs);
    }

    public function getCommit($sha)
    {
        $this->setShaId($sha);
        $commit = $this->curlGet('/projects/:id/repository/commits/:sha');
        return $commit;
    }

    public function getPipeline($id)
    {
        $this->setPipelineId($id);
        $pipeline = $this->curlGet('/projects/:id/pipelines/:pipeline_id');
        return $pipeline;
    }

    private function curlGet($url, $get = [])
    {
        $url = $this->getUrl($url, $get);

//        echo $url . PHP_EOL;
        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 4
        );

        $options = [];
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        $headers = array(
            "PRIVATE-TOKEN: " . $this->pat,
            "Content-Type: application/json",
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return json_decode($result);
    }

    private function getUrl($url, $get = [])
    {
        $find = array_keys($this->vars);
        $replace = array_values($this->vars);
        $new_string = str_ireplace($find, $replace, $url);
        $url = $this->baseUrl . $new_string;
        $symbol = ((strpos($url, '?') === false && count($get)) ? '?' : '');
        $params = (count($get) > 0 ? http_build_query($get) : '');
        return $url . $symbol . $params;
    }

}


//
//$pat = 'glpat-f4RkynUADeeeHoU4bx3R';
//$pid = '11513622';

$g = new GItlab(getenv('GL_PAT'), getenv('GL_PROJECT_ID'));
$g->getPipelines();
