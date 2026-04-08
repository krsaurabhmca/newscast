import React from 'react';
import { Tabs } from 'expo-router';
import { Feather } from '@expo/vector-icons';

export default function TabLayout() {
  return (
    <Tabs screenOptions={{
      tabBarActiveTintColor: '#ff3c00',
      headerShown: false,
      headerStyle: { backgroundColor: '#fff' },
      headerTitleStyle: { fontWeight: '900' },
    }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Home',
          tabBarIcon: ({ color }) => <Feather name="home" size={24} color={color} />,
        }}
      />
      <Tabs.Screen
        name="categories"
        options={{
          title: 'Categories',
          tabBarIcon: ({ color }) => <Feather name="grid" size={24} color={color} />,
        }}
      />
      <Tabs.Screen
        name="videos"
        options={{
          title: 'Videos',
          tabBarIcon: ({ color }) => <Feather name="video" size={24} color={color} />,
        }}
      />
      <Tabs.Screen
        name="digital"
        options={{
          title: 'E-Paper',
          tabBarIcon: ({ color }) => <Feather name="book-open" size={24} color={color} />,
        }}
      />
      <Tabs.Screen
        name="more"
        options={{
          title: 'More',
          tabBarIcon: ({ color }) => <Feather name="more-horizontal" size={24} color={color} />,
        }}
      />
    </Tabs>
  );
}
