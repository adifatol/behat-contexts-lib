<?php

use Behat\Behat\Exception\Exception;
use Behat\Behat\Context\BehatContext;

/**
 * OpenLayers context.
 *
 * Works for openLayers
 */
class OpenLayersContext extends BehatContext
{

	private $known_events = array('zoomend','moveend');

	const MAP_OBJ = 'Map.map';

	public function __construct(array $parameters)
	{
		// Initialize your context here
		$this->_parameters = $parameters;
	}

	public function getParameter($name)
	{
		if (count($this->_parameters) === 0) {

			throw new \Exception('Parameters not loaded!');
		} else {
			$parameters = $this->_parameters;
			return (isset($parameters[$name])) ? $parameters[$name] : null;
		}
	}

	private function prepareForMapEvent($event){
		if (!in_array($event, $this->known_events)){
			throw new Exception('Unknown event: ' . $event);
		}

		$this->getMainContext()->getSession()->executeScript(
				"if (".self::MAP_OBJ."['eventStatus'] == undefined){".self::MAP_OBJ."['eventStatus'] = [];}"
				.self::MAP_OBJ."['eventStatus']['$event']=false;"
				.self::MAP_OBJ.".events.register('$event', map , function(e){"
				.self::MAP_OBJ."['eventStatus']['$event']=true;
	});
				");
	}

	private function waitForEvent($event, $sec = 5){
		$this->getMainContext()->getSession()->wait($sec * 1000, self::MAP_OBJ."['eventStatus']['$event']===true");
	}

	// 	private function waitForEvent();

	/**
	 * @Then /^I should see the map should be centered on lat "([^"]*)" and lon "([^"]*)"$/
	 */
	public function iShouldSeetheMapShouldBeCenteredOnLatAndLon($lat, $lon){
		$this->getMainContext()->getSession()->wait(5000, self::MAP_OBJ . ".getCenter().transform(" . self::MAP_OBJ . ".projection, " . self::MAP_OBJ .".displayProjection).lat ==={$lat}");
		$this->getMainContext()->getSession()->wait(5000, self::MAP_OBJ . ".getCenter().transform(" . self::MAP_OBJ . ".projection, " . self::MAP_OBJ .".displayProjection).lon ==={$lon}");
	}
	
	/**
	 * @Given /^I center the map on lon "([^"]*)" and lat "([^"]*)"$/
	 */
	public function iCenterTheMapOnLonAndLat($lon, $lat)
	{
		$event = 'moveend';
//
		$this->prepareForMapEvent($event);
		$this->getMainContext()->getSession()->executeScript(self::MAP_OBJ.".setCenter(
				new OpenLayers.LonLat($lon, $lat).transform(".self::MAP_OBJ.".displayProjection, ".self::MAP_OBJ.".projection))");
		$this->waitForEvent($event);
	}

	/**
	 * @Given /^I zoom the map to scale "([^"]*)"$/
	 */
	public function iZoomTheMapToScale($scale)
	{
		$event = 'zoomend';

		$this->prepareForMapEvent($event);
		$this->getMainContext()->getSession()->executeScript(self::MAP_OBJ.".zoomToScale($scale)");
		$this->waitForEvent($event);
	}

	/**
	 * @Given /^I zoom the map to level "([^"]*)"$/
	 */
	public function iZoomTheMapToLevel($level)
	{
		$event = 'zoomend';

		$this->prepareForMapEvent($event);
		$this->getMainContext()->getSession()->executeScript(self::MAP_OBJ.".zoomTo($level)");
		$this->waitForEvent($event);
	}

	/**
	 * @Then /^there should be no visible markers on "([^"]*)" layer$/
	 */
	public function thereShouldBeNoVisibleMarkersOnLayer($layer_name)
	{
		$map = self::MAP_OBJ;

		$script = <<<JS
			theLayers = {$map}.getLayersByName('$layer_name');

			if (!theLayers instanceof Array){
					return;//no layer
			}

			for(layer in theLayers){
				console.log(layer);
			}

JS;
		$this->getMainContext()->getSession()->executeScript($script);

		$this->getMainContext()->iWait(150000);
	}
	
	/**
	 * @Then /^there should be "([^"]*)" markers on the "([^"]*)" layer$/
	 */
	public function thereShouldBeMarkersOnTheLayer($count, $layerName){
		/* check if layer exists */
		$this->getMainContext()->getSession()->wait(5000, self::MAP_OBJ . ".getLayersByName('{$layerName}').length > 0");
		/* check if the number of markers on the layer is equal to the count parameter */
		$this->getMainContext()->getSession()->wait(5000, self::MAP_OBJ . ".getLayersByName('{$layerName}')[0].markers.length ==={$count}");
}
	
	/**
	 * @then /^there shuld be a "([^"]*) layer visible on the map$/ 
	 */
	public function thereShouldBeALayerVisibleOnTheMap($layerName){
		/* check if layer exists */
		$this->getMainContext()->getSession()->wait(5000, self::MAP_OBJ . ".getLayersByName('{$layerName}').length > 0");
		/* check if the layer is visible */
		$this->getMainContext()->getSession()->wait(5000, self::MAP_OBJ . ".getLayersByName('{$layerName}')[0].visibility === true");
	}
}