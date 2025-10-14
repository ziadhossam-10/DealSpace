import { configureStore } from '@reduxjs/toolkit';
import authReducer from '../features/auth/authSlice';
import { authApi } from '../features/auth/authApi';
import { peopleApi } from '../features/people/peopleApi';
import { usersApi } from '../features/users/usersApi';
import { groupsApi } from '../features/groups/groupsApi';
import { pondsApi } from '../features/ponds/pondsApi';
import { stagesApi } from '../features/stages/stagesApi';
import { customFieldsApi } from '../features/customFields/customFieldsApi';
import { notificationsApi } from '../features/notifications/notificationsApi';
import { notesApi } from '../features/people/notesApi';
import { callsApi } from '../features/people/callsApi';
import { emailsApi } from '../features/people/emailsApi';
import { textMessagesApi } from '../features/people/textMessagesApi';
import { emailAccountsApi } from '../features/manageEmails/emailAccountsApi';
import { emailTemplatesApi } from '../features/emailTemplates/emailTemplatesApi';
import { textMessageTemplatesApi } from '../features/textMessageTemplates/textMessageTemplatesApi';
import { activitiesApi } from '../features/people/activitiesApi';
import { dealsApi } from '../features/deals/dealsApi';
import { dealTypesApi } from '../features/dealTypes/dealTypesApi';
import { appointmentTypesApi } from '../features/appointmentTypes/appointmentTypesApi';
import { appointmentOutcomesApi } from '../features/appointmentOutcomes/appointmentOutcomesApi';
import { appointmentsApi } from '../features/people/appointmentsApi';
import { tasksApi } from '../features/people/tasksApi';
import { calendarEventsApi } from '../features/calendar/calendarEventsApi';
import { agentActivityApi } from '../features/reporting/agentActivity/agentActivityApi';
import { teamsApi } from '../features/teams/teamsApi';
import { eventTypesApi } from '../features/reporting/propertyReport/eventTypesApi';
import { propertyReportsApi } from '../features/reporting/propertyReport/propertyReportsApi';
import { leadSourceApi } from '../features/reporting/leadSource/leadSourceApi';
import { callsReportApi } from '../features/reporting/calls/callsApi';
import { textsReportApi } from '../features/reporting/texts/textsApi';
import { apiKeysApi } from '../features/apiKeys/apiKeysApi';
import { trackingScriptsApi } from '../features/trackingScripts/trackingScriptsApi';
import { marketingApi } from '../features/reporting/marktingReport/marketingApi';
import { dealsReportApi } from '../features/reporting/deals/dealsApi';
import { leaderboardApi } from '../features/reporting/leaderboard/leaderboard-api';
import { appointmentReportApi } from '../features/reporting/appointments/appointments-api';
import { subscriptionApi } from '../features/subscriptions/subscriptionApi';
import { leadFlowRulesApi } from '../features/leadFlowRules/leadFlowRulesApi';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    [authApi.reducerPath]: authApi.reducer,
    [peopleApi.reducerPath]: peopleApi.reducer,
    [usersApi.reducerPath]: usersApi.reducer,
    [groupsApi.reducerPath]: groupsApi.reducer,
    [pondsApi.reducerPath]: pondsApi.reducer,
    [stagesApi.reducerPath]: stagesApi.reducer,
    [customFieldsApi.reducerPath]: customFieldsApi.reducer,
    [notificationsApi.reducerPath]: notificationsApi.reducer,
    [notesApi.reducerPath]: notesApi.reducer,
    [callsApi.reducerPath]: callsApi.reducer,
    [textMessagesApi.reducerPath]: textMessagesApi.reducer,
    [emailAccountsApi.reducerPath]: emailAccountsApi.reducer,
    [emailTemplatesApi.reducerPath]: emailTemplatesApi.reducer,
    [emailsApi.reducerPath]: emailsApi.reducer,
    [textMessageTemplatesApi.reducerPath]: textMessageTemplatesApi.reducer,
    [activitiesApi.reducerPath]: activitiesApi.reducer,
    [dealsApi.reducerPath]: dealsApi.reducer,
    [dealTypesApi.reducerPath]: dealTypesApi.reducer,
    [appointmentTypesApi.reducerPath]: appointmentTypesApi.reducer,
    [appointmentOutcomesApi.reducerPath]: appointmentOutcomesApi.reducer,
    [appointmentsApi.reducerPath]: appointmentsApi.reducer,
    [tasksApi.reducerPath]: tasksApi.reducer,
    [calendarEventsApi.reducerPath]: calendarEventsApi.reducer,
    [agentActivityApi.reducerPath]: agentActivityApi.reducer,
    [teamsApi.reducerPath]: teamsApi.reducer,
    [eventTypesApi.reducerPath]: eventTypesApi.reducer,
    [propertyReportsApi.reducerPath]: propertyReportsApi.reducer,
    [leadSourceApi.reducerPath]: leadSourceApi.reducer,
    [callsReportApi.reducerPath]: callsReportApi.reducer,
    [textsReportApi.reducerPath]: textsReportApi.reducer,
    [apiKeysApi.reducerPath]: apiKeysApi.reducer,
    [trackingScriptsApi.reducerPath]: trackingScriptsApi.reducer,
    [marketingApi.reducerPath]: marketingApi.reducer,
    [dealsReportApi.reducerPath]: dealsReportApi.reducer,
    [leaderboardApi.reducerPath]: leaderboardApi.reducer,
    [appointmentReportApi.reducerPath]: appointmentReportApi.reducer,
    [subscriptionApi.reducerPath]: subscriptionApi.reducer,
    [leadFlowRulesApi.reducerPath]: leadFlowRulesApi.reducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware()
      .concat(authApi.middleware)
      .concat(peopleApi.middleware)
      .concat(usersApi.middleware)
      .concat(groupsApi.middleware)
      .concat(pondsApi.middleware)
      .concat(stagesApi.middleware)
      .concat(customFieldsApi.middleware)
      .concat(notificationsApi.middleware)
      .concat(notesApi.middleware)
      .concat(callsApi.middleware)
      .concat(textMessagesApi.middleware)
      .concat(emailAccountsApi.middleware)
      .concat(emailTemplatesApi.middleware)
      .concat(emailsApi.middleware)
      .concat(textMessageTemplatesApi.middleware)
      .concat(activitiesApi.middleware)
      .concat(dealsApi.middleware)
      .concat(dealTypesApi.middleware)
      .concat(appointmentTypesApi.middleware)
      .concat(appointmentOutcomesApi.middleware)
      .concat(appointmentsApi.middleware)
      .concat(tasksApi.middleware)
      .concat(calendarEventsApi.middleware)
      .concat(agentActivityApi.middleware)
      .concat(teamsApi.middleware)
      .concat(eventTypesApi.middleware)
      .concat(propertyReportsApi.middleware)
      .concat(leadSourceApi.middleware)
      .concat(callsReportApi.middleware)
      .concat(textsReportApi.middleware)
      .concat(apiKeysApi.middleware)
      .concat(trackingScriptsApi.middleware)
      .concat(marketingApi.middleware)
      .concat(dealsReportApi.middleware)
      .concat(leaderboardApi.middleware)
      .concat(appointmentReportApi.middleware)
      .concat(subscriptionApi.middleware)
      .concat(leadFlowRulesApi.middleware)
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;