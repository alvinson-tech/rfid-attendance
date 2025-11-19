#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>

// WiFi Credentials
const char* ssid = "POCO M2";
const char* password = "12345678";

// Server URL (Change to your server's IP/domain)
const char* serverUrl = "http://10.85.167.103/rfid_attendance/api.php";

// RFID Pins
#define SS_PIN 5
#define RST_PIN 22
#define BUZZER_PIN 4

// PWM settings for passive buzzer
#define BUZZER_RESOLUTION 8

MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  delay(1000);
  
  // Configure PWM for buzzer (passive buzzer needs frequency)
  // ESP32 Arduino Core 3.x uses ledcAttach instead of ledcSetup + ledcAttachPin
  ledcAttach(BUZZER_PIN, 2000, BUZZER_RESOLUTION); // GPIO 4, 2kHz frequency, 8-bit resolution
  
  // Initialize SPI and RFID
  SPI.begin();
  rfid.PCD_Init();
  
  // Connect to WiFi
  Serial.println("\n\n=== ESP32 RFID System Starting ===");
  Serial.print("Connecting to WiFi: ");
  Serial.println(ssid);
  
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWiFi Connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
    Serial.print("Signal Strength (RSSI): ");
    Serial.println(WiFi.RSSI());
    beep(2, 100, 2200); // Double short beep - WiFi connected
  } else {
    Serial.println("\n\nFAILED TO CONNECT TO WIFI!");
    Serial.println("Please check:");
    Serial.println("1. WiFi SSID and password are correct");
    Serial.println("2. Router is on 2.4GHz band");
    Serial.println("3. ESP32 is within range");
    // No beep for WiFi failure
  }
  
  Serial.println("Place RFID card near reader...");
}

void loop() {
  // Check for new cards
  if (!rfid.PICC_IsNewCardPresent()) {
    return;
  }
  
  // Read card
  if (!rfid.PICC_ReadCardSerial()) {
    return;
  }
  
  // Get RFID code
  String rfidCode = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    rfidCode += String(rfid.uid.uidByte[i] < 0x10 ? "0" : "");
    rfidCode += String(rfid.uid.uidByte[i], HEX);
  }
  rfidCode.toUpperCase();
  
  Serial.print("RFID Code: ");
  Serial.println(rfidCode);
  
  // Send to server
  sendToServer(rfidCode);
  
  // Halt PICC
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
  
  delay(1000); // Prevent multiple reads
}

void sendToServer(String rfidCode) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    String url = String(serverUrl) + "?action=scan&rfid=" + rfidCode;
    http.begin(url);
    
    int httpCode = http.GET();
    
    if (httpCode > 0) {
      String payload = http.getString();
      Serial.println("Server Response: " + payload);
      
      if (payload.indexOf("success") > -1) {
        Serial.println("✓ Attendance marked successfully!");
        beep(1, 200, 2500); // 1 short beep - success
      } else if (payload.indexOf("already_present") > -1) {
        Serial.println("⚠ Already marked present!");
        beep(1, 200, 2500); // 1 short beep - treat as success
      } else if (payload.indexOf("not_registered") > -1) {
        Serial.println("✗ Card not registered!");
        beep(3, 150, 1000); // 3 short beeps - not registered
      } else if (payload.indexOf("no_session") > -1) {
        Serial.println("⚠ No active session!");
        beep(1, 800, 1500); // 1 long beep - no session
      } else {
        Serial.println("✗ Unknown response!");
        beep(3, 150, 1000); // 3 short beeps - error
      }
    } else {
      Serial.println("HTTP Error: " + String(httpCode));
      // No beep for HTTP errors
    }
    
    http.end();
  } else {
    Serial.println("WiFi Disconnected!");
    // No beep for WiFi disconnect
  }
}

// Enhanced beep function for passive buzzer with Low Level Trigger
// times: number of beeps
// duration: duration of each beep in milliseconds
// frequency: tone frequency in Hz (higher = higher pitch)
void beep(int times, int duration, int frequency) {
  for (int i = 0; i < times; i++) {
    // Set frequency and turn on buzzer
    ledcChangeFrequency(BUZZER_PIN, frequency, BUZZER_RESOLUTION);
    ledcWrite(BUZZER_PIN, 128); // 50% duty cycle for good volume
    delay(duration);
    
    // Turn off buzzer
    ledcWrite(BUZZER_PIN, 0);
    
    // Pause between beeps (except for the last one)
    if (i < times - 1) {
      delay(150);
    }
  }
}