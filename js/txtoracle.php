<?php
$cardname = $_GET["name"];

// Fetch oracle
$url = "http://gatherer.wizards.com/Pages/Card/Details.aspx";
$oracle_page = curl_get($url, array("name" => $cardname));
try {
    $card = extract_card($oracle_page);
} catch (Exception $e) {
    echo "<b>Error:</b> " . $e->getMessage();
    return;
}

display_tooltip($card);

function curl_get($url, array $get = NULL, array $options = array())
{
    $defaults = array(
        CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get),
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 4
    );

    $ch = curl_init();
    curl_setopt_array($ch, ($options + $defaults));
    if (!$result = curl_exec($ch)) {
        trigger_error(curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

function extract_card($html_content)
{
    $result = array();

    $posName = strpos($html_content, "Card Name:");

    if (!$posName) {
        throw new Exception("No card with this name found.");
    }

    $posMana = strpos($html_content, "Mana Cost:");
    $posTypes = strpos($html_content, "Types:");
    $posText = strpos($html_content, "Card Text:");
    $posFlavor = strpos($html_content, "Flavor Text:");
    $posPT = strpos($html_content, "P/T:");
    $posLoyalty = strpos($html_content, "Loyalty:");
    $posExp = strpos($html_content, "Expansion:");

    $result['name'] = extract_name($html_content, $posName, $posMana, $posTypes);
    $result['mana'] = extract_mana($html_content, $posMana);

    $result['types'] = extract_types($html_content, $posTypes, $posText, $posFlavor, $posPT);
    $result['text'] = extract_text($html_content, $posText, $posFlavor, $posPT, $posLoyalty, $posExp);
    $result['pt'] = extract_pt($html_content, $posPT, $posExp);
    $result['loyalty'] = extract_loyalty($html_content, $posLoyalty, $posExp);

    return $result;
}

function extract_name($html_content, $posName, $posMana, $posTypes)
{
    if ($posMana) {
        $name = substr($html_content, $posName, $posMana - $posName);
    } else {
        $name = substr($html_content, $posName, $posTypes - $posName);
    }
    return trim(str_replace('Card Name:', '', strip_tags($name)));
}

function extract_mana($html_content, $posMana)
{
    if (!$posMana) {
        return null;
    }

    $posCmc = strpos($html_content, "Converted Mana Cost:");
    $mana = substr($html_content, $posMana, $posCmc - $posMana);
    $mana = str_replace('medium', 'small', $mana);
    $mana = str_replace('src="', 'src="http://gatherer.wizards.com', $mana);
    $mana = trim(str_replace("Mana Cost:", "", strip_tags($mana, "<img>")));
    return $mana;
}

function extract_types($html_content, $posTypes, $posText, $posFlavor, $posPT)
{
    if ($posText) {
        $endTypes = $posText - $posTypes;
    } else {
        // no rules text
        if ($posFlavor) {
            $endTypes = $posFlavor - $posTypes;
        } else {
            // no flavor Text
            $endTypes = $posPT - $posTypes;
        }
    }

    $types = strip_tags(substr($html_content, $posTypes, $endTypes));
    $types = trim(str_replace("Types:", "", $types));
    return $types;
}

function extract_text($html_content, $posText, $posFlavor, $posPT, $posLoyalty, $posExp)
{
    if (!$posText) {
        return null;
    }

    if ($posFlavor) {
        $endText = $posFlavor - $posText;
    } else if ($posPT) {
        $endText = $posPT - $posText;
    } else if ($posLoyalty) {
        $endText = $posLoyalty - $posText;
    } else {
        $endText = $posExp - $posText;
    }

    $text = substr($html_content, $posText, $endText);
    $text = str_replace('src="', 'src="http://gatherer.wizards.com', $text);
    $text = strip_tags($text, "<img><br>");
    $text = trim(str_replace("Card Text:", "", $text));
    return $text;
}

function extract_pt($html_content, $posPT, $posExp)
{
    if (!$posPT) {
        return null;
    }

    $endPT = $posExp - $posPT;
    $PT = strip_tags(substr($html_content, $posPT, $endPT));
    $PT = trim(str_replace("P/T:", "", $PT));
    return $PT;
}

function extract_loyalty($html_content, $posLoyalty, $posExp)
{
    if (!$posLoyalty) {
        return null;
    }

    $endLoyalty = $posExp - $posLoyalty;
    $loyalty = strip_tags(substr($html_content, $posLoyalty, $endLoyalty));
    $loyalty = trim(str_replace("Loyalty:", "", $loyalty));
    return $loyalty;
}

function display_tooltip($card)
{
    echo "<b>" . $card['name'] . "</b><br />";
    if($card['mana']) {
        echo $card['mana'] . "<br />";
    }
    echo $card['types'] . "<br />";
    if($card['text']) {
        echo $card['text'] . "<br />";
    }
    if($card['pt']) {
        echo $card['pt'] . "<br />";
    }
    if($card['loyalty']) {
        echo $card['loyalty'] . "<br />";
    }
}

?>