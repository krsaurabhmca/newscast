import { DarkTheme, DefaultTheme, ThemeProvider, Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'react-native';

import { AnimatedSplashOverlay } from '@/components/animated-icon';

SplashScreen.preventAutoHideAsync();

export default function RootLayout() {
  return (
    <ThemeProvider value={DefaultTheme}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />
      <AnimatedSplashOverlay />
      <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: '#ffffff' } }}>
        <Stack.Screen name="index" />
        <Stack.Screen name="articles" />
        <Stack.Screen name="categories" />
        <Stack.Screen name="polls" />
        <Stack.Screen name="media" />
        <Stack.Screen name="explore" />
      </Stack>
    </ThemeProvider>
  );
}
