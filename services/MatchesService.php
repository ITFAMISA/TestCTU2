<?php

/**
 * MatchesService - Servicio optimizado para obtener partidos de fútbol
 * 
 * Características:
 * - Caché automático para optimizar requests
 * - Rate limiting para evitar límites de API
 * - Configuración flexible de ligas
 * - Error handling robusto
 * - Logs detallados para debugging
 */
class MatchesService {
    
    private $apiKey;
    private $apiHost = 'api-football-v1.p.rapidapi.com';
    private $cacheDir;
    private $cacheExpiry = 1800; // 30 minutos
    private $rateLimitDelay = 1; // 1 segundo entre requests
    private $lastRequestTime = 0;
    
    // Configuración actualizada de ligas (IDs actuales 2025)
    private $leagues = [
        'liga_mx' => [
            'id' => 262,
            'name' => 'Liga MX',
            'country' => 'Mexico',
            'seasons' => [2025, 2024],
            'priority' => 1
        ],
        'mls' => [
            'id' => 253,
            'name' => 'Major League Soccer',
            'country' => 'USA',
            'seasons' => [2025, 2024],
            'priority' => 2
        ],
        'leagues_cup' => [
            'id' => 772,
            'name' => 'Leagues Cup',
            'country' => 'International',
            'seasons' => [2025, 2024, 2023],
            'priority' => 3
        ],
        'concacaf' => [
            'id' => 16,
            'name' => 'CONCACAF Champions Cup',
            'country' => 'International',
            'seasons' => [2024, 2025],
            'priority' => 4
        ],
        'champions_league' => [
            'id' => 2,
            'name' => 'UEFA Champions League',
            'country' => 'Europe',
            'seasons' => [2024, 2025],
            'priority' => 5
        ],
        'premier_league' => [
            'id' => 39,
            'name' => 'Premier League',
            'country' => 'England',
            'seasons' => [2024, 2025],
            'priority' => 6
        ],
        'la_liga' => [
            'id' => 140,
            'name' => 'La Liga',
            'country' => 'Spain',
            'seasons' => [2024, 2025],
            'priority' => 7
        ]
    ];
    
    public function __construct($apiKey = null, $cacheDir = null) {
        $this->apiKey = $apiKey ?: ($_ENV['RAPIDAPI_KEY'] ?? '1dec45416emsh269d1d4adce38e2p136b9bjsn951df1a6d6c5');
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../cache/matches';
        $this->ensureCacheDirectory();
    }
    
    /**
     * Obtener partidos de múltiples ligas
     */
    public function getMatches($leagueKeys = ['liga_mx', 'mls'], $options = []) {
        try {
            $dateFrom = $options['date_from'] ?? date('Y-m-d');
            $dateTo = $options['date_to'] ?? date('Y-m-d', strtotime('+30 days'));
            $useCache = $options['use_cache'] ?? true;
            $maxMatches = $options['max_matches'] ?? null;
            
            $results = [
                'success' => true,
                'matches_by_league' => [],
                'total_matches' => 0,
                'date_range' => ['from' => $dateFrom, 'to' => $dateTo],
                'cached_leagues' => [],
                'api_calls_made' => 0,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            foreach ($leagueKeys as $leagueKey) {
                if (!isset($this->leagues[$leagueKey])) {
                    $this->log("Warning: Liga '$leagueKey' no configurada");
                    continue;
                }
                
                try {
                    $matches = $this->getLeagueMatches($leagueKey, $dateFrom, $dateTo, $useCache);
                    
                    if (!empty($matches)) {
                        // Aplicar límite si se especifica
                        if ($maxMatches && count($matches) > $maxMatches) {
                            $matches = array_slice($matches, 0, $maxMatches);
                        }
                        
                        $results['matches_by_league'][$leagueKey] = $matches;
                        $results['total_matches'] += count($matches);
                        
                        // Verificar si se usó caché
                        if ($this->wasLastRequestCached($leagueKey, $dateFrom, $dateTo)) {
                            $results['cached_leagues'][] = $leagueKey;
                        } else {
                            $results['api_calls_made']++;
                        }
                    }
                    
                } catch (Exception $e) {
                    $this->log("Error obteniendo partidos para $leagueKey: " . $e->getMessage());
                    // Continuar con otras ligas en caso de error
                    continue;
                }
            }
            
            $this->log("Servicio completado. Total partidos: {$results['total_matches']}, API calls: {$results['api_calls_made']}");
            return $results;
            
        } catch (Exception $e) {
            $this->log("Error general en getMatches: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Obtener partidos de una liga específica
     */
    public function getLeagueMatches($leagueKey, $dateFrom, $dateTo, $useCache = true) {
        if (!isset($this->leagues[$leagueKey])) {
            throw new Exception("Liga '$leagueKey' no configurada");
        }
        
        $league = $this->leagues[$leagueKey];
        
        // Verificar caché primero
        if ($useCache) {
            $cachedMatches = $this->getCachedMatches($leagueKey, $dateFrom, $dateTo);
            if ($cachedMatches !== null) {
                $this->log("Usando caché para $leagueKey");
                return $cachedMatches;
            }
        }
        
        // Intentar con diferentes temporadas
        foreach ($league['seasons'] as $season) {
            try {
                $matches = $this->fetchMatchesFromAPI($league['id'], $season, $dateFrom, $dateTo);
                
                if (!empty($matches)) {
                    // Procesar y limpiar datos
                    $processedMatches = $this->processMatches($matches, $league);
                    
                    // Guardar en caché
                    if ($useCache) {
                        $this->saveToCache($leagueKey, $dateFrom, $dateTo, $processedMatches);
                    }
                    
                    $this->log("Obtenidos " . count($processedMatches) . " partidos para {$league['name']} (temporada $season)");
                    return $processedMatches;
                }
                
            } catch (Exception $e) {
                $this->log("Error en temporada $season para {$league['name']}: " . $e->getMessage());
                continue;
            }
        }
        
        $this->log("No se encontraron partidos para {$league['name']}");
        return [];
    }
    
    /**
     * Realizar request a la API con rate limiting
     */
    private function fetchMatchesFromAPI($leagueId, $season, $dateFrom, $dateTo) {
        // Rate limiting
        $this->enforceRateLimit();
        
        $url = "https://{$this->apiHost}/v3/fixtures";
        $params = [
            'league' => $leagueId,
            'season' => $season,
            'from' => $dateFrom,
            'to' => $dateTo,
            'timezone' => 'America/Mexico_City'
        ];
        
        $headers = [
            'X-RapidAPI-Key: ' . $this->apiKey,
            'X-RapidAPI-Host: ' . $this->apiHost,
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '?' . http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP $httpCode");
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decodificando JSON: " . json_last_error_msg());
        }
        
        return $data['response'] ?? [];
    }
    
    /**
     * Procesar y limpiar datos de partidos
     */
    private function processMatches($rawMatches, $league) {
        $processed = [];
        
        foreach ($rawMatches as $match) {
            $processed[] = [
                'fixture_id' => $match['fixture']['id'],
                'date' => $match['fixture']['date'],
                'timestamp' => $match['fixture']['timestamp'],
                'status' => $match['fixture']['status']['short'] ?? 'NS',
                'status_long' => $match['fixture']['status']['long'] ?? 'Not Started',
                'elapsed' => $match['fixture']['status']['elapsed'] ?? null,
                'venue' => $match['fixture']['venue']['name'] ?? 'TBD',
                'city' => $match['fixture']['venue']['city'] ?? '',
                'home_team' => [
                    'id' => $match['teams']['home']['id'],
                    'name' => $match['teams']['home']['name'],
                    'logo' => $match['teams']['home']['logo'] ?? ''
                ],
                'away_team' => [
                    'id' => $match['teams']['away']['id'],
                    'name' => $match['teams']['away']['name'],
                    'logo' => $match['teams']['away']['logo'] ?? ''
                ],
                'goals' => [
                    'home' => $match['goals']['home'],
                    'away' => $match['goals']['away']
                ],
                'score' => $match['score'] ?? [],
                'league' => [
                    'id' => $league['id'],
                    'name' => $league['name'],
                    'country' => $league['country']
                ]
            ];
        }
        
        // Ordenar por fecha
        usort($processed, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });
        
        return $processed;
    }
    
    /**
     * Sistema de caché
     */
    private function getCachedMatches($leagueKey, $dateFrom, $dateTo) {
        $cacheFile = $this->getCacheFilePath($leagueKey, $dateFrom, $dateTo);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $cacheTime = filemtime($cacheFile);
        if (time() - $cacheTime > $this->cacheExpiry) {
            unlink($cacheFile);
            return null;
        }
        
        $content = file_get_contents($cacheFile);
        return json_decode($content, true);
    }
    
    private function saveToCache($leagueKey, $dateFrom, $dateTo, $data) {
        $cacheFile = $this->getCacheFilePath($leagueKey, $dateFrom, $dateTo);
        file_put_contents($cacheFile, json_encode($data));
    }
    
    private function getCacheFilePath($leagueKey, $dateFrom, $dateTo) {
        $filename = "{$leagueKey}_{$dateFrom}_{$dateTo}.json";
        return $this->cacheDir . '/' . $filename;
    }
    
    private function wasLastRequestCached($leagueKey, $dateFrom, $dateTo) {
        $cacheFile = $this->getCacheFilePath($leagueKey, $dateFrom, $dateTo);
        return file_exists($cacheFile) && (time() - filemtime($cacheFile) <= $this->cacheExpiry);
    }
    
    /**
     * Rate limiting
     */
    private function enforceRateLimit() {
        $timeSinceLastRequest = microtime(true) - $this->lastRequestTime;
        if ($timeSinceLastRequest < $this->rateLimitDelay) {
            $sleepTime = $this->rateLimitDelay - $timeSinceLastRequest;
            usleep($sleepTime * 1000000);
        }
        $this->lastRequestTime = microtime(true);
    }
    
    /**
     * Utilidades
     */
    private function ensureCacheDirectory() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        error_log("[$timestamp] MatchesService: $message");
    }
    
    /**
     * Obtener información de configuración
     */
    public function getAvailableLeagues() {
        return array_map(function($key, $league) {
            return [
                'key' => $key,
                'name' => $league['name'],
                'country' => $league['country'],
                'priority' => $league['priority']
            ];
        }, array_keys($this->leagues), $this->leagues);
    }
    
    /**
     * Limpiar caché
     */
    public function clearCache($leagueKey = null) {
        if ($leagueKey) {
            $pattern = $this->cacheDir . "/{$leagueKey}_*.json";
            foreach (glob($pattern) as $file) {
                unlink($file);
            }
        } else {
            foreach (glob($this->cacheDir . "/*.json") as $file) {
                unlink($file);
            }
        }
    }
}