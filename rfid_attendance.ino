#include <SPI.h>
#include <MFRC522.h>
#include <WiFi.h>
#include <HTTPClient.h>

// WiFi Credentials
const char* ssid = "Hogwarts Great Hall Wi-Fi";
const char* password = "6esqqZxU";

// Server URL (Change to your server's IP/domain)
const char* serverUrl = "http://192.168.1.3/rfid_attendance/api.php";

// RFID Pins
#define SS_PIN 5
#define RST_PIN 22
#define BUZZER_PIN 4

MFRC522 rfid(SS_PIN, RST_PIN);

void setup() {
  Serial.begin(115200);
  pinMode(BUZZER_PIN, OUTPUT);
  
  // Initialize SPI and RFID
  SPI.begin();
  rfid.PCD_Init();
  
  // Connect to WiFi
  Serial.print("Connecting to WiFi");
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("\nWiFi Connected!");
  Serial.print("IP Address: ");
  Serial.println(WiFi.localIP());
  Serial.println("Place RFID card near reader...");
  
  // Beep to indicate ready
  beep(2, 100);
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
        beep(1, 200); // Success beep
      } else if (payload.indexOf("not_registered") > -1) {
        beep(3, 100); // Error beep
        Serial.println("Card not registered!");
      } else if (payload.indexOf("no_session") > -1) {
        beep(2, 150); // Warning beep
        Serial.println("No active session!");
      } else {
        beep(3, 100); // Error beep
      }
    } else {
      Serial.println("HTTP Error: " + String(httpCode));
      beep(4, 100); // Connection error beep
    }
    
    http.end();
  } else {
    Serial.println("WiFi Disconnected!");
    beep(5, 100);
  }
}

void beep(int times, int duration) {
  for (int i = 0; i < times; i++) {
    digitalWrite(BUZZER_PIN, HIGH);
    delay(duration);
    digitalWrite(BUZZER_PIN, LOW);
    delay(100);
  }
}