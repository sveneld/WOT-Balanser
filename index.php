<?php
set_time_limit(0);

include_once('Object.php');
include_once('Clan.php');
include_once('Player.php');
include_once('Tank.php');
include_once('Team.php');
include_once('TeamPlayer.php');
include_once('DataContainer.php');
include_once('Api.php');
include_once('Cache.php');



class Balancer
{
    /**
     * @var Api
     */
    private $api;
    private $clans = array();
    private $teams = array();
    private $tanks = array();

    private function auth()
    {
        if (isset($_REQUEST['status']) && $_REQUEST['status'] == 'ok') {
            echo '<pre>';
            $this->api->setAccessToken($_REQUEST['access_token']);
        }else{
            $param = new DataContainer();
            $param->class = 'auth';
            $param->method = 'login';
            $param->params = array('expires_at' => 2 * 7 * 24 * 60 * 60, 'redirect_uri' => 'http://wg.com/');
            $param->redirect = true;
            $this->api->getData($param);
        }
    }

    private function getClans()
    {
        $param = new DataContainer();
        $param->class = 'globalwar';
        $param->method = 'top';
        $param->params = array('order_by' => 'provinces_count', 'map_id' => 1);
        $result = $this->api->getData($param);
        $topClans = array_slice($result->data, 0, 10);
        $randClans = array_rand($topClans, 2);
        foreach ($randClans as $clanId){
            $this->clans[] = new Clan($clanId, $topClans[$clanId]);
        }
    }

    private function getClansPlayers()
    {
        foreach ($this->clans as $clan){
            $param = new DataContainer();
            $param->class = 'clan';
            $param->method = 'info';
            $param->params = array('clan_id' => $clan->getId(), 'fields' => 'members,members_count');
            $result = $this->api->getData($param);
            if (!empty($result->data->{$clan->getId()}->members) && $result->data->{$clan->getId()}->members_count >= 15){
                $clan->addPlayers($result->data->{$clan->getId()}->members);
            }else{
                $this->clans = array();
                $this->getClans();
                $this->getClansPlayers();
                break;
            }
        }
    }

    private function getTanks()
    {
        $param = new DataContainer();
        $param->class = 'encyclopedia';
        $param->method = 'tanks';
        $param->params = array('fields' => 'level,tank_id');
        $result = $this->api->getData($param);

        foreach ($result->data as $tankId => $info) {
            if ($info->level >= 4 && $info->level <= 6) {
                $param = new DataContainer();
                $param->class = 'encyclopedia';
                $param->method = 'tankinfo';
                $param->params = array('tank_id' => $tankId, 'fields' => 'name,gun_damage_min,gun_damage_max,max_health,level');
                $tankResult = $this->api->getData($param);

                $this->tanks[$tankId] = new Tank($tankId, $tankResult->data->{$tankId});
            }
        }
    }

    private function getPlayersTanks()
    {
        $allowedTanks = array();
        foreach ($this->tanks as $tank){
            $allowedTanks[] = $tank->getId();
        }

        foreach ($this->clans as $clan){
            $players = $clan->getPlayers();
            foreach ($players as $player){
                sleep(0.05);
                $param = new DataContainer();
                $param->class = 'tanks';
                $param->method = 'stats';
                $param->params = array('account_id' => $player->getId(), 'in_garage' => 1, 'fields' => 'mark_of_mastery,tank_id');
                try {
                    $result = $this->api->getData($param);
                }catch (Exception $e){
                    $clan->removePlayer($player->getId());
                    continue;
                }
                if (empty($result->data->{$player->getId()})){
                    $clan->removePlayer($player->getId());
                    continue;
                }
                foreach ($result->data->{$player->getId()} as $num => $playerTank) {
                    if (!in_array($playerTank->tank_id, $allowedTanks)) {
                        unset($result->data->{$player->getId()}[$num]);
                    }else{
                        $result->data->{$player->getId()}[$num]->name = $this->tanks[$playerTank->tank_id]->getData()->name;
                        $result->data->{$player->getId()}[$num]->level = $this->tanks[$playerTank->tank_id]->getData()->level;
                        $result->data->{$player->getId()}[$num]->gun_damage_min = $this->tanks[$playerTank->tank_id]->getData()->gun_damage_min;
                        $result->data->{$player->getId()}[$num]->gun_damage_max = $this->tanks[$playerTank->tank_id]->getData()->gun_damage_max;
                        $result->data->{$player->getId()}[$num]->max_health = $this->tanks[$playerTank->tank_id]->getData()->max_health;
                        $result->data->{$player->getId()}[$num]->balance_weight =
                            $playerTank->mark_of_mastery * 25 +
                            $playerTank->max_health +
                            $playerTank->gun_damage_min * 0.5 +
                            $playerTank->gun_damage_max * 0.5 +
                            $playerTank->level * 10;

                    }
                }

                if (empty($result->data->{$player->getId()})){
                    $clan->removePlayer($player->getId());
                }else{
                    $player->addTanks($result->data->{$player->getId()});
                }
            }
        }
    }

    public function init()
    {
        $this->api = new Api();
        $this->auth();
        $this->getClans();
        $this->getClansPlayers();
        $this->getTanks();
        $this->getPlayersTanks();
    }

    public function makeBalance(){
        $firstClan = $this->clans[0];
        $secondClan = $this->clans[1];

        $firstClanPlayers = $firstClan->getPlayers();
        $team = new Team(1, array());

        $randClanPlayers = array_rand($firstClanPlayers, 15);
        $i = 0;
        foreach ($randClanPlayers as $playerId){
            $player = $firstClanPlayers[$playerId];
            $teamPlayer = new stdClass();
            $teamPlayer->name = $player->getData()->account_name;

            $playerTanks = $player->getTanks();
            $tankRand = array_rand($playerTanks, 1);
            $teamPlayer->tank_name = $playerTanks[$tankRand]->getData()->name;
            $teamPlayer->tank_level = $playerTanks[$tankRand]->getData()->level;
            $teamPlayer->tank_balance_weight = $playerTanks[$tankRand]->getData()->balance_weight;

            $team->addTeamPlayer($i, $teamPlayer);
            $i++;
        }

        $this->teams[]= $team;
        $averageBalanceWeight = $team->getTeamBalanceAverageWeightSplit();

        $secondClanPlayers = $secondClan->getPlayers();
        $team = new Team(2, array());
        $randClanPlayers = array_rand($secondClanPlayers, 15);

        $i = 0;
        $balancePart = 0;
        foreach ($randClanPlayers as $playerId){
            $player = $secondClanPlayers[$playerId];
            $teamPlayer = new stdClass();
            $teamPlayer->name = $player->getData()->account_name;

            $playerTanks = $player->getTanks();
            shuffle($playerTanks);
            $tankSelected = false;
            $j = 1;
            while (!$tankSelected){
                foreach ($playerTanks as $tankId => $tank){
                    if ($tank->getData()->balance_weight < $averageBalanceWeight[$balancePart] + $averageBalanceWeight[$balancePart]*0.005+$j &&
                        $tank->getData()->balance_weight > $averageBalanceWeight[$balancePart] - $averageBalanceWeight[$balancePart]*0.005+$j
                    ){
                        $teamPlayer->tank_name = $playerTanks[$tankId]->getData()->name;
                        $teamPlayer->tank_level = $playerTanks[$tankId]->getData()->level;
                        $teamPlayer->tank_balance_weight = $playerTanks[$tankId]->getData()->balance_weight;
                        $tankSelected = true;
                        break;
                    }
                }
                $j += 0.001;
                if ($j > 0.01){
                    $playerTanks = $player->getTanks();
                    $tankRand = array_rand($playerTanks, 1);
                    $teamPlayer->tank_name = $playerTanks[$tankRand]->getData()->name;
                    $teamPlayer->tank_level = $playerTanks[$tankRand]->getData()->level;
                    $teamPlayer->tank_balance_weight = $playerTanks[$tankRand]->getData()->balance_weight;
                    $tankSelected = true;
                }
            }

            $team->addTeamPlayer($i, $teamPlayer);
            $i++;
            if ($i % 3 == 0 ){
                $balancePart++;
            }
        }
        $this->teams[]= $team;
    }

    public function outputBalanceResult(){
        echo '<div align="center">';
        echo '<table width="800" border="1">';
        echo '<tr>';
        foreach($this->teams as $team){
                echo '<td>';
                    $teamPlayer = $team->getTeamPlayers();
                    foreach ($teamPlayer as $player){
                        echo '<table width="400" border="0">';
                            echo '<tr>';
                                echo '<td>';
                                    echo $player->getData()->name.' '.$player->getData()->tank_name;
                                echo '</td>';
                            echo '</tr>';
                        echo '</table>';
                    }

                echo '</td>';
        }
        echo '</tr>';
        echo '</table>';
        echo '</div>';
    }
}


$balanser = new Balancer();
$balanser->init();
$balanser->makeBalance();
$balanser->outputBalanceResult();